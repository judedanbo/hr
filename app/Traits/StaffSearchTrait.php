<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;


trait StaffSearchTrait
{

  function scopeSearchPerson(Builder $query, $search)
  {
    return $query->whereHas('person', function ($q) use ($search) {
      $q->search($search);
    });
  }
  /**
   * Scope a query to search for staff .
   *
   * @param Builder $query
   * @param string|null $search
   * @return Builder
   */

  public function scopeSearchStaff(Builder $query, $search)
  {
    return $query->when($search, function () use ($query, $search) {
      $query->where('staff_number', 'like', "%{$search}%");
      $query->orWhere('file_number', 'like', "%{$search}%");
      $query->orWhere('old_staff_number', 'like', "%{$search}%");
      $query->orWhereYear('hire_date', $search);
      $query->orWhereRaw('monthname(hire_date) like ?', ['%' . $search . '%']);
    });
  }
  function scopeSearch(Builder $query, $search)
  {
    return $query->searchStaff($search)
      ->orWhere(function ($q) use ($search) {
        $q->searchPerson($search);
      });;
  }
}
