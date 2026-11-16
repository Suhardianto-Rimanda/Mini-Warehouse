<?php

namespace App\Console\Commands;

use App\Jobs\LowStockReportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LowStockReportCommand extends Command
{
    protected $signature = 'stock:low-report';

    protected $description = 'Queue a low stock CSV report.';

    public function handle(): int
    {
        $path = 'reports/low-stock-'.Str::uuid().'.csv';

        LowStockReportJob::dispatch(10, $path);

        $this->info('Report queued.');
        $this->line("Output: storage/app/private/{$path}");

        return self::SUCCESS;
    }
}