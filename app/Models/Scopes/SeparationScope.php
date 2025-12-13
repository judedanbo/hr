<?php

namespace App\Models\Scopes;

use App\Enums\EmployeeStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SeparationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // Get the most recent status for each staff member
        $latestStatusSubquery = \App\Models\Status::selectRaw('MAX(id)')
            ->whereColumn('staff_id', 'institution_person.id')
            ->whereNull('deleted_at');

        $builder->whereHas('statuses', function ($query) use ($latestStatusSubquery) {
            $query->whereIn('status.id', $latestStatusSubquery)
                ->where(function ($query) {
                    // Non-active status that is current (no end_date or end_date in future)
                    $query->where(function ($query) {
                        $query->whereNot('status', EmployeeStatusEnum::Active->value)
                            ->where(function ($query) {
                                $query->whereNull('status.end_date')
                                    ->orWhere('status.end_date', '>', now());
                            });
                    })
                        // OR Active status that has ended
                        ->orWhere(function ($query) {
                            $query->where('status', EmployeeStatusEnum::Active->value)
                                ->whereNotNull('status.end_date')
                                ->where('status.end_date', '<=', now());
                        });
                });
        });
    }
}
