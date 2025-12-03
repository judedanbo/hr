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
            $query->where(function ($query) {
                $query->whereNot(
                    'status',
                    EmployeeStatusEnum::Active->value,
                )->where(function ($query) {
                    $query->whereNull('status.end_date')
                        ->orWhere('status.end_date', '>', now());
                });
            })->orWhere(function ($query) {
                $query->where('status', EmployeeStatusEnum::Active->value)
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now());
            });
        });
    }
}
