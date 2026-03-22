<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = $this->categoryQuery()
            ->orderBy('categories.name')
            ->get()
            ->map(fn (object $category): array => $this->formatCategory($category));

        return response()->json(['categories' => $categories], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:categories,name'],
        ]);

        $now = now();

        $categoryId = DB::table('categories')->insertGetId([
            'name' => $validated['name'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Kategória bola úspešne vytvorená.',
            'category' => $this->findCategory($categoryId),
        ], Response::HTTP_CREATED);
    }

    public function show(string $category)
    {
        $foundCategory = $this->findCategory((int) $category);

        if ($foundCategory === null) {
            return $this->notFoundResponse();
        }

        return response()->json(['category' => $foundCategory], Response::HTTP_OK);
    }

    public function update(Request $request, string $category)
    {
        $categoryId = (int) $category;

        if ($this->findCategory($categoryId) === null) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
        ]);

        DB::table('categories')->where('id', $categoryId)->update([
            'name' => $validated['name'],
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Kategória bola úspešne aktualizovaná.',
            'category' => $this->findCategory($categoryId),
        ], Response::HTTP_OK);
    }

    public function destroy(string $category)
    {
        $categoryId = (int) $category;

        if ($this->findCategory($categoryId) === null) {
            return $this->notFoundResponse();
        }

        DB::table('categories')->where('id', $categoryId)->delete();

        return response()->json([
            'message' => 'Kategória bola odstránená.',
        ], Response::HTTP_OK);
    }

    private function findCategory(int $categoryId): ?array
    {
        $category = $this->categoryQuery()
            ->where('categories.id', $categoryId)
            ->first();

        if ($category === null) {
            return null;
        }

        return $this->formatCategory($category);
    }

    private function categoryQuery()
    {
        return DB::table('categories')
            ->leftJoin('note_category', 'categories.id', '=', 'note_category.category_id')
            ->select(
                'categories.id',
                'categories.name',
                'categories.created_at',
                'categories.updated_at'
            )
            ->selectRaw('COUNT(note_category.note_id) as notes_count')
            ->groupBy(
                'categories.id',
                'categories.name',
                'categories.created_at',
                'categories.updated_at'
            );
    }

    private function formatCategory(object $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
            'notes_count' => (int) $category->notes_count,
        ];
    }

    private function notFoundResponse()
    {
        return response()->json([
            'message' => 'Kategória nenájdená.',
        ], Response::HTTP_NOT_FOUND);
    }
}
