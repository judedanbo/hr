<?php

namespace App\Traits;

trait PersonTraits
{
  public function getFullNameAttribute()
  {
    return $this->first_name . ' ' . $this->last_name;
  }

  public function getFullNameWithTitleAttribute()
  {
    return $this->title . ' ' . $this->full_name;
  }

  public function getFullNameWithTitleAndSuffixAttribute()
  {
    return $this->full_name_with_title . ', ' . $this->suffix;
  }

  public function scopeSearch($query, $search)
  {
    return $query->when($search, function ($query, $search) {
      $terms = explode(' ', $search);
      foreach ($terms as $term) {
        $query->where(function ($searchName) use ($term) {
          $searchName->where('first_name', 'like', "%{$term}%");
          $searchName->orWhere('title', 'like', "%{$term}%");
          $searchName->orWhere('other_names', 'like', "%{$term}%");
          $searchName->orWhere('surname', 'like', "%{$term}%");
          $searchName->orWhere('date_of_birth', 'like', "%{$term}%");
          $searchName->orWhereRaw('monthname(date_of_birth) like ?', [$term]);
        });
      }
    });
  }
}
