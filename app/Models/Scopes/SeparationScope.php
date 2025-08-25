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
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereHas('statuses', function ($query) {
            $query->whereIn('status', [
                EmployeeStatusEnum::Left->value,
                EmployeeStatusEnum::Termination->value,
                EmployeeStatusEnum::Resignation->value,
                EmployeeStatusEnum::Retired->value,
                EmployeeStatusEnum::Dismissed->value,
                EmployeeStatusEnum::Deceased->value,
                EmployeeStatusEnum::Voluntary->value
            ])->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>', now());
            });
        });
    }
}
