<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Laravel E-commerce API",
 *     version="1.0.0",
 *     description="API dokumentacja dla Laravel E-commerce Backend - Sklep internetowy z odzieżą używaną",
 *     @OA\Contact(
 *         email="biuro@maxsoft.pl"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token autoryzacyjny Sanctum"
 * )
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Koszulka T-Shirt"),
 *     @OA\Property(property="description", type="string", example="Opis produktu"),
 *     @OA\Property(property="price", type="number", format="float", example=99.99),
 *     @OA\Property(property="sale_price", type="number", format="float", nullable=true, example=79.99),
 *     @OA\Property(property="current_price", type="number", format="float", example=79.99),
 *     @OA\Property(property="category", type="string", nullable=true, example="Odzież"),
 *     @OA\Property(property="size", type="string", nullable=true, example="M"),
 *     @OA\Property(property="condition", type="string", nullable=true, example="good"),
 *     @OA\Property(property="brand", type="string", nullable=true, example="Nike"),
 *     @OA\Property(property="color", type="string", nullable=true, example="Czarny"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="stock_quantity", type="integer", example=10),
 *     @OA\Property(property="available_stock", type="integer", example=8),
 *     @OA\Property(property="sku", type="string", nullable=true, example="SKU-12345"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
