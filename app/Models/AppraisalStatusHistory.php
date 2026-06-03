<?php

namespace App\Models;

use App\Enums\AppraisalStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'appraisal_id',
        'status',
        'actor_id',
        'comment',
    ];

    protected $casts = [
        'status' => AppraisalStatusEnum::class,
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
