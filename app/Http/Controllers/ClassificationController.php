<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    public function suggest(Request $request, ClassificationService $service): JsonResponse
    {
        $user = $request->user();

        $description = (string) $request->query('description', '');
        $type = (string) $request->query('type', '');

        $category = $service->suggest($user, $description, $type !== '' ? $type : null);

        return response()->json([
            'category' => $category instanceof Category ? [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'color' => $category->color,
                'icon' => $category->icon,
            ] : null,
        ]);
    }
}
