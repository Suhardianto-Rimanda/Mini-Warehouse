<?php

namespace App\Console\Commands;

use App\Jobs\GenerateStockMutationReportJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Command;

class GenerateStockReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:generate-report {date : Report date in YYYY-MM-DD format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a job to generate the daily stock mutation report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date');

        $validator = Validator::make(
            ['date' => $date],
            ['date' => ['required', 'date_format:Y-m-d']],
            [
                'date.required' => 'The report date is required.',
                'date.date_format' => 'Enter a valid date in YYYY-MM-DD format.',
            ]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first('date'));

            return self::INVALID;
        }

        GenerateStockMutationReportJob::dispatch($date);

        $this->info("Report generation for {$date} has been dispatched.");

        return self::SUCCESS;
    }
}
