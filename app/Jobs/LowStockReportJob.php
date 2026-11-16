<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Product;
use RuntimeException;

class LowStockReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $threshold,
        public string $path,
    ) {}

    public function handle(): void
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Cannot open CSV stream.');
        }

        try {
            if (fputcsv($stream, ['SKU', 'Product', 'Stock', 'Unit'], ',', '"', '') === false) {
                throw new RuntimeException('Cannot write CSV header.');
            }

            $products = Product::query()
                ->select(['sku', 'name', 'stock', 'unit'])
                ->where('stock', '<=', $this->threshold)
                ->orderBy('stock')
                ->orderBy('sku')
                ->cursor();

            foreach ($products as $product) {
                $row = [
                    $product->sku,
                    $product->name,
                    $product->stock,
                    $product->unit,
                ];

                foreach ($row as $index => $value) {
                    if (!is_string($value)) {
                        continue;
                    }

                    $textToCheck = Str::ltrim($value);

                    if (Str::startsWith($textToCheck, ['=', '+', '-', '@'])) {
                        $row[$index] = "'".$value;
                    }
                }
            }

            if (!rewind($stream)) {
                throw new RuntimeException('Cannot rewind CSV stream.');
            }

            if (!Storage::disk('local')->put($this->path, $stream)) {
                throw new RuntimeException('Cannot save report.');
            }
        } finally {
            fclose($stream);
        }

        Log::info('Low stock report generated.', [
            'path' => $this->path,
            'threshold' => $this->threshold,
        ]);
    }
}