<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json(Product::latest()->get());
    }

   
    public function store(Request $request): JsonResponse
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $product = Product::create($validated);

        return response()->json($product, 201);
    }


    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }


    public function update(Request $request, Product $product): JsonResponse
    {
        //  dd($request->all());
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $product->update($validated);

        return response()->json($product);
    }


    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
