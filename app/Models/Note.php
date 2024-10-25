<?php

namespace App\Models;

use App\Enums\NoteTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'note',
        'note_date',
        'note_type',
        'notable_type',
        'notable_id',
        'created_by',
        'url',
    ];

    protected $casts = [
        'note_date' => 'datetime',
        'note_type' => NoteTypeEnum::class,
    ];

    public function notable()
    {
        return $this->morphTo();
    }

    function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
