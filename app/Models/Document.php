<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $fillable = [
        'document_type',
        'document_title',
        'document_number',
        'document_file',
        'file_type',
        'file_name',
        'document_status',
        'document_remarks',
        'documentable_type',
        'documentable_id',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
