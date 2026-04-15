<?php

namespace App\Observers;

use App\Models\Qualification;
use Illuminate\Support\Facades\Cache;

class QualificationObserver
{
    public function saved(Qualification $qualification): void
    {
        $this->flush();
    }

    public function deleted(Qualification $qualification): void
    {
        $this->flush();
    }

    public function restored(Qualification $qualification): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        Cache::increment('qual-report:version');
    }
}
