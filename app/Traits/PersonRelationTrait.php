<?php

namespace App\Traits;

use App\Models\Person;

trait PersonRelationTrait
{
  public function person()
  {
    return $this->belongsTo(Person::class);
  }
  public function personWithTrashed()
  {
    return $this->belongsTo(Person::class)->withTrashed();
  }
}
