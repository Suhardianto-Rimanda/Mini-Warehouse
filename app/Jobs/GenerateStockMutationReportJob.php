<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\StockMutation;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateStockMutationReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3; 
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $date)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Validator::make(
            ['date' => $this->date],
            ['date' => ['required', 'date_format:Y-m-d']],
            [
                'date.required' => 'The report date is required.',
                'date.date_format' => 'Enter a valid date in YYYY-MM-DD format.',
            ]
        )->validate();

        $disk = Storage::disk('local');
        $path = "reports/stock-mutations-{$this->date}.csv";
        $temporaryPath = $path.'.'.Str::uuid().'.tmp';
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the CSV stream.');
        }

        try {
            $this->writeCsvRow(
                $stream,
                ['Date', 'SKU', 'Product', 'Type', 'Quantity', 'User']
            );

            $mutations = StockMutation::with([
                'product:id,sku,name',
                'user:id,name',
            ])
                ->whereDate('created_at', $this->date)
                ->lazyById(500);

            foreach ($mutations as $mutation) {
                $this->writeCsvRow($stream, [
                    $mutation->created_at->format('Y-m-d H:i:s'),
                    $mutation->product?->sku ?? '',
                    $mutation->product?->name ?? '',
                    $mutation->type,
                    $mutation->quantity,
                    $mutation->user?->name ?? '',
                ]);
            }

            if (! rewind($stream)) {
                throw new RuntimeException('Unable to rewind the CSV stream.');
            }

            if (! $disk->put($temporaryPath, $stream)) {
                throw new RuntimeException('Unable to save the temporary report.');
            }

            if (! $disk->move($temporaryPath, $path)) {
                throw new RuntimeException('Unable to publish the report.');
            }
        } finally {
            fclose($stream);
            $disk->delete($temporaryPath);
        }

        Log::info('Stock mutation report generated.', [
            'date' => $this->date,
            'path' => $disk->path($path),
        ]);
    }

    private function writeCsvRow($stream, array $row): void
    {
        foreach ($row as $index => $value) {
            if (is_string($value) && preg_match('/^[\s=+\-@=+-@]/u', $value)) {
                $row[$index] = "'".$value;
            }
        }

        if (fputcsv($stream, $row, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write a CSV row.');
        }
    }
}
