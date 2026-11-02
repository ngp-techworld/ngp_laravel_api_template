<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Expired bearer tokens are useless; delete them a day after they expire.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
