<?php

namespace App\Traits;

use App\Models\Note;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait PersonNotesTrait
{

  public function notes(): MorphMany
  {
    return $this->morphMany(Note::class, 'notable')->latest();
  }
}
