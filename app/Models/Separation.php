<?php

namespace App\Models;

use App\Enums\EmployeeStatusEnum;
use App\Models\Scopes\SeparationScope;
use App\Traits\LogAllTraits;
use App\Traits\PersonNotesTrait;
use App\Traits\PersonRelationTrait;
use App\Traits\PersonStatusTrait;
use App\Traits\StaffSearchTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([SeparationScope::class])]
class Separation extends Model
{
    use
        HasFactory,
        LogAllTraits,
        PersonRelationTrait,
        PersonStatusTrait,
        PersonNotesTrait,
        StaffSearchTrait;

    protected $table = 'institution_person';
    protected $casts = [
        'hire_date' => 'date',
        'end_date' => 'date',
    ];


    // public function scopeSearch(Builder $query, $search)
    // {
    //     return $query->when($search, function () use ($query, $search) {
    //         $query->where('staff_number', 'like', "%{$search}%");
    //         $query->orWhere('file_number', 'like', "%{$search}%");
    //         $query->orWhere('old_staff_number', 'like', "%{$search}%");
    //         $query->orWhereYear('hire_date', $search);
    //         $query->orWhereRaw('monthname(hire_date) like ?', ['%' . $search . '%']);
    //     });
    // }
}
