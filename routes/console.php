<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// RN-07: caduca diariamente las solicitudes activas inactivas por >90 días.
Schedule::command('solicitudes:caducar')->dailyAt('01:00')->withoutOverlapping();
