<?php

use App\Console\Commands\LowStockReportCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(LowStockReportCommand::class)->everyMinute();