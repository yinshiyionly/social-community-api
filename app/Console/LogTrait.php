<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;

trait LogTrait
{
    public function infoLog($msg)
    {
        $this->info($msg);
        Log::channel('console')->info($msg);
    }

    public function errorLog($msg)
    {
        $this->info($msg);
        Log::channel('console')->error($msg);
    }

    public function warnLog($msg)
    {
        $this->warn($msg);
        Log::channel('console')->warning($msg);
    }
}
