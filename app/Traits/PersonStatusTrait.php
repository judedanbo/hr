<?php

namespace App\Traits;

use App\Models\Status;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait PersonStatusTrait
{

  public function statuses(): HasMany
  {
    return $this->hasMany(Status::class, 'staff_id', 'id')
      ->latest();
  }
}
