<?php

namespace App\Models;

use App\Enums\ContactTypeEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'person_id',
        'contact_type',
        'contact',
        'valid_end',
    ];

    protected $casts = [
        'contact_type' => ContactTypeEnum::class,
        'valid_end' => 'date',
    ];

    /**
     * Get the person that owns the contact.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Returns true when this contact is an Audit Service organisational email
     * (domain exactly equals audit.gov.gh, case-insensitive).
     * These addresses must not be deletable by end users.
     */
    public function isProtectedOrgEmail(): bool
    {
        if ($this->contact_type !== ContactTypeEnum::EMAIL) {
            return false;
        }
        $email = strtolower((string) $this->contact);
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return false;
        }
        $domain = substr($email, $atPos + 1);

        return $domain === 'audit.gov.gh';
    }
}
