<?php

namespace App\Services\Staff;

/**
 * Splits a single "full name" string into the surname / first name / other names
 * shape used by the people table.
 *
 * Names on external lists are written "First Middle Surname", but surnames are not
 * always a single token (e.g. "Gloria Owusu Afram" has the surname "Owusu Afram").
 * When an organisational email is available it is used to find the surname boundary,
 * because the local part is built from the person's names.
 */
class StaffNameSplitter
{
    /**
     * Split a full name, optionally using an email address to locate the surname.
     *
     * @return array{surname: string, first_name: string, other_names: string, strategy: string}
     */
    public function split(string $fullName, ?string $email = null): array
    {
        $words = $this->words($fullName);

        if ($words === []) {
            return $this->result([], [], 'empty');
        }

        if (count($words) === 1) {
            return $this->result($words, [], 'single_word');
        }

        $surnameLength = $this->surnameLengthFromEmail($words, $email);

        return $this->result(
            array_slice($words, -($surnameLength ?? 1)),
            array_slice($words, 0, count($words) - ($surnameLength ?? 1)),
            $surnameLength === null ? 'last_word' : 'email',
        );
    }

    /**
     * Split a name on whitespace only, so that a hyphenated compound such as
     * "Kyei-Yirenkyi" survives as a single word and keeps its original spelling.
     *
     * @return list<string>
     */
    public function words(string $name): array
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        return array_values(array_filter(explode(' ', $name), static fn (string $word): bool => $word !== ''));
    }

    /**
     * Work out how many trailing tokens of the name form the surname, using the
     * email local part. Returns null when the email gives no usable signal.
     *
     * Both orderings are considered because the organisation issues addresses as
     * "first.surname" and, for some staff, "surname.first".
     *
     * @param  list<string>  $words
     */
    private function surnameLengthFromEmail(array $words, ?string $email): ?int
    {
        $segments = $this->emailSegments($email);

        if ($segments === []) {
            return null;
        }

        /** @var list<string> $candidates */
        $candidates = [end($segments), reset($segments)];

        foreach ($candidates as $candidate) {
            $length = $this->matchTrailingWords($words, $candidate);

            if ($length !== null) {
                return $length;
            }
        }

        return null;
    }

    /**
     * Return how many trailing words of the name the candidate covers, or null.
     *
     * Comparison is done on a squashed (letters-only) form so that "Owusu-Afriyie",
     * "Owusu Afriyie" and "owusuafriyie" are all treated as the same surname.
     *
     * @param  list<string>  $words
     */
    private function matchTrailingWords(array $words, string $candidate): ?int
    {
        $candidate = $this->squash($candidate);

        if ($candidate === '') {
            return null;
        }

        $maxLength = min(count($words) - 1, 3);

        for ($length = 1; $length <= $maxLength; $length++) {
            $tail = $this->squash(implode('', array_slice($words, -$length)));

            if ($tail === $candidate) {
                return $length;
            }
        }

        return null;
    }

    /**
     * Split the local part of an email into its dot-separated segments.
     *
     * @return list<string>
     */
    private function emailSegments(?string $email): array
    {
        if ($email === null || trim($email) === '') {
            return [];
        }

        $local = strtolower(trim(explode('@', trim($email))[0]));
        $segments = array_values(array_filter(explode('.', $local), static fn (string $segment): bool => $segment !== ''));

        return $segments;
    }

    /**
     * Break a name into comparable tokens, treating punctuation as a separator.
     *
     * @return list<string>
     */
    public function tokenize(string $name): array
    {
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? '';

        return array_values(array_filter(explode(' ', trim($name)), static fn (string $token): bool => $token !== ''));
    }

    /**
     * Reduce a string to lowercase letters and digits only, dropping separators.
     */
    public function squash(string $value): string
    {
        return strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '');
    }

    /**
     * Build an order-independent key for a set of name tokens, so that
     * "Seth Joe Tetteh" and "Tetteh Seth Joe" produce the same key.
     */
    public function tokenKey(string ...$parts): string
    {
        $tokens = [];

        foreach ($parts as $part) {
            foreach ($this->tokenize($part) as $token) {
                $tokens[] = $this->squash($token);
            }
        }

        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));
        sort($tokens);

        return implode(' ', $tokens);
    }

    /**
     * @param  list<string>  $surname
     * @param  list<string>  $others
     * @return array{surname: string, first_name: string, other_names: string, strategy: string}
     */
    private function result(array $surname, array $others, string $strategy): array
    {
        return [
            'surname' => implode(' ', $surname),
            'first_name' => $others === [] ? '' : $others[0],
            'other_names' => implode(' ', array_slice($others, 1)),
            'strategy' => $strategy,
        ];
    }
}
