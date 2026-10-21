<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = DocumentCategory::query()
            ->forOrganization($request->user()->organization_id)
            ->with('types')
            ->orderBy('code')
            ->get();

        return response()->json(['data' => $categories]);
    }
}
