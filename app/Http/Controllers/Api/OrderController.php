<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::with('items.product')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load('items.product'));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $order = DB::transaction(function () use ($validated) {
                $items = collect($validated['items']);
                $productIds = $items->pluck('product_id')->unique()->values();

                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $totalCents = 0;
                $itemsToPersist = [];

                foreach ($items as $index => $item) {
                    $product = $products->get($item['product_id']);

                    if (! $product) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_id" => ['Selected product is invalid.'],
                        ]);
                    }

                    if ($product->stock_quantity < $item['quantity']) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => [
                                "Insufficient stock for product ID {$product->id}. Available stock: {$product->stock_quantity}.",
                            ],
                        ]);
                    }

                    $unitPriceCents = $this->decimalToCents($product->price);
                    $subtotalCents = $unitPriceCents * (int) $item['quantity'];
                    $totalCents += $subtotalCents;

                    $itemsToPersist[] = [
                        'product_id' => $product->id,
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => $this->centsToDecimal($unitPriceCents),
                        'subtotal' => $this->centsToDecimal($subtotalCents),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $product->stock_quantity -= (int) $item['quantity'];
                    $product->save();
                }

                $order = Order::create([
                    'customer_name' => $validated['customer_name'],
                    'status' => $validated['status'] ?? 'pending',
                    'total_amount' => $this->centsToDecimal($totalCents),
                ]);

                foreach ($itemsToPersist as &$row) {
                    $row['order_id'] = $order->id;
                }

                OrderItem::insert($itemsToPersist);

                return $order->load('items.product');
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($order, 201);
    }

    private function decimalToCents(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function centsToDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
