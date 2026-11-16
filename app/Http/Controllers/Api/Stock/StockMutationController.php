<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMutationCollection;
use App\Http\Resources\StockMutationResource;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMutationController extends Controller
{
    public function index()
    {
        return response()->json(new StockMutationCollection(
            StockMutation::with(['product.category', 'user'])
                ->latest('created_at')
                ->simplePaginate(15)
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'    => ['required', 'uuid', 'exists:products,id'],
            'type'          => ['required', 'in:in,out'],
            'quantity'      => ['required', 'integer', 'min:1', 'max:2147483647'],
            'notes'         => ['nullable', 'string'],
        ]);

        $stockMutation = DB::transaction(function () use ($request, $data) {
            if ($data['type'] === 'in') {
                $change = $data['quantity'];
            } else {
                $change = -$data['quantity'];
            }

            $this->applyStockChanges([
                $data['product_id'] => $change,
            ]);

            $data['user_id'] = $request->user()->id;

            return StockMutation::create($data);
        }, 3);

        return response()->json(new StockMutationResource(
            $stockMutation->load(['product.category', 'user'])
        ), 201);
    }

    public function show(StockMutation $stockMutation)
    {
        return response()->json(new StockMutationResource(
            $stockMutation->load(['product.category', 'user'])
        ));
    }

    public function update(Request $request, StockMutation $stockMutation)
    {
        $data = $request->validate([
            'product_id'    => ['required', 'uuid', 'exists:products,id'],
            'type'          => ['required', 'in:in,out'],
            'quantity'      => ['required', 'integer', 'min:1', 'max:2147483647'],
            'notes'         => ['nullable', 'string'],
        ]);

        $stockMutation = DB::transaction(function () use ($stockMutation, $data) {
            $stockMutation = StockMutation::lockForUpdate()->findOrFail($stockMutation->id);

            if ($stockMutation->type === 'in') {
                $oldChange = $stockMutation->quantity;
            } else {
                $oldChange = -$stockMutation->quantity;
            }

            if ($data['type'] === 'in') {
                $newChange = $data['quantity'];
            } else {
                $newChange = -$data['quantity'];
            }

            $changes = [$stockMutation->product_id => -$oldChange];

            if (!isset($changes[$data['product_id']])) {
                $changes[$data['product_id']] = 0;
            }

            $changes[$data['product_id']] += $newChange;

            $this->applyStockChanges($changes);

            $stockMutation->update($data);

            return $stockMutation;
        }, 3);

        return response()->json(new StockMutationResource(
            $stockMutation->load(['product.category', 'user'])
        ));
    }

    public function destroy(StockMutation $stockMutation)
    {
        return DB::transaction(function () use ($stockMutation) {
            $stockMutation = StockMutation::lockForUpdate()->findOrFail($stockMutation->id);

            if ($stockMutation->type === 'in') {
                $change = -$stockMutation->quantity;
            } else {
                $change = $stockMutation->quantity;
            }

            $this->applyStockChanges([
                $stockMutation->product_id => $change,
            ]);

            $stockMutation->delete();

            return response()->json([
                'message' => 'Stock mutation successfully deleted.',
            ]);
        }, 3);
    }

    private function applyStockChanges(array $changes): void
    {
        $products = Product::whereIn('id', array_keys($changes))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        abort_if(
            $products->count() !== count($changes),
            404,
            'Product not found.'
        );

        foreach ($products as $product) {
            $stock = $product->stock + $changes[$product->id];

            if ($stock < 0 || $stock > 2147483647) {
                throw ValidationException::withMessages([
                    'quantity' => 'Final stock must be within the range of 0 to 2147483647.',
                ]);
            }

            $product->stock = $stock;
            $product->save();
        }
    }
}
