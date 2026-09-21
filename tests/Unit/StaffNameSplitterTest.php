<?php

namespace Tests\Unit;

use App\Services\Staff\StaffNameSplitter;
use PHPUnit\Framework\TestCase;

class StaffNameSplitterTest extends TestCase
{
    private StaffNameSplitter $splitter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->splitter = new StaffNameSplitter;
    }

    public function test_splits_a_three_part_name_using_the_email_surname(): void
    {
        $result = $this->splitter->split('Micah Thomas Nunoo', 'thomas.nunoo@audit.gov.gh');

        $this->assertSame('Nunoo', $result['surname']);
        $this->assertSame('Micah', $result['first_name']);
        $this->assertSame('Thomas', $result['other_names']);
        $this->assertSame('email', $result['strategy']);
    }

    public function test_recognises_a_two_word_surname_from_a_hyphenated_email(): void
    {
        $result = $this->splitter->split('Gloria  Owusu Afram', 'gloria.owusu-afram@audit.gov.gh');

        $this->assertSame('Owusu Afram', $result['surname']);
        $this->assertSame('Gloria', $result['first_name']);
        $this->assertSame('', $result['other_names']);
        $this->assertSame('email', $result['strategy']);
    }

    public function test_keeps_a_hyphenated_surname_intact(): void
    {
        $result = $this->splitter->split('Gertrude Owusu-Afriyie', 'gertrude.owusu-afriyie@audit.gov.gh');

        $this->assertSame('Owusu-Afriyie', $result['surname']);
        $this->assertSame('Gertrude', $result['first_name']);
    }

    public function test_matches_a_surname_written_without_its_separator_in_the_email(): void
    {
        $result = $this->splitter->split('Abigail Abena Asante Djin', 'abigail.asantedjin@audit.gov.gh');

        $this->assertSame('Asante Djin', $result['surname']);
        $this->assertSame('Abigail', $result['first_name']);
        $this->assertSame('Abena', $result['other_names']);
    }

    public function test_handles_emails_written_surname_first(): void
    {
        $result = $this->splitter->split('Seth Joe Tetteh', 'tetteh.seth@audit.gov.gh');

        $this->assertSame('Tetteh', $result['surname']);
        $this->assertSame('Seth', $result['first_name']);
        $this->assertSame('Joe', $result['other_names']);
        $this->assertSame('email', $result['strategy']);
    }

    public function test_falls_back_to_the_last_word_when_the_email_only_covers_part_of_the_surname(): void
    {
        $result = $this->splitter->split('Emmanuel Ofori-Mensah', 'emmanuel.mensah@audit.gov.gh');

        $this->assertSame('Ofori-Mensah', $result['surname']);
        $this->assertSame('Emmanuel', $result['first_name']);
        $this->assertSame('last_word', $result['strategy']);
    }

    public function test_falls_back_to_the_last_word_without_an_email(): void
    {
        $result = $this->splitter->split('Ebow Debrah Fynn');

        $this->assertSame('Fynn', $result['surname']);
        $this->assertSame('Ebow', $result['first_name']);
        $this->assertSame('Debrah', $result['other_names']);
        $this->assertSame('last_word', $result['strategy']);
    }

    public function test_treats_a_single_word_as_the_surname(): void
    {
        $result = $this->splitter->split('Mensah');

        $this->assertSame('Mensah', $result['surname']);
        $this->assertSame('', $result['first_name']);
        $this->assertSame('single_word', $result['strategy']);
    }

    public function test_handles_an_empty_name(): void
    {
        $result = $this->splitter->split('   ');

        $this->assertSame('', $result['surname']);
        $this->assertSame('', $result['first_name']);
        $this->assertSame('', $result['other_names']);
        $this->assertSame('empty', $result['strategy']);
    }

    public function test_never_consumes_the_whole_name_as_a_surname(): void
    {
        $result = $this->splitter->split('Kofi Mensah', 'kofi.kofi-mensah@audit.gov.gh');

        $this->assertSame('Mensah', $result['surname']);
        $this->assertSame('Kofi', $result['first_name']);
    }

    public function test_token_key_ignores_word_order_and_punctuation(): void
    {
        $this->assertSame(
            $this->splitter->tokenKey('Seth Joe Tetteh'),
            $this->splitter->tokenKey('Tetteh', 'Seth', 'Joe'),
        );

        $this->assertSame(
            $this->splitter->tokenKey('Owusu-Afriyie Gertrude'),
            $this->splitter->tokenKey('Gertrude', 'Owusu Afriyie'),
        );
    }

    public function test_squash_reduces_a_value_to_letters_only(): void
    {
        $this->assertSame('owusuafriyie', $this->splitter->squash('Owusu-Afriyie'));
        $this->assertSame('oforimensah', $this->splitter->squash(' Ofori  Mensah '));
    }
}
