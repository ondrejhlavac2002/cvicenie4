<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public function index()
    {
        $notes = DB::table('notes')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'notes' => $this->hydrateNotes($notes)->values(),
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:128'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(Note::STATUSES)],
            'is_pinned' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $now = now();

        $noteId = DB::table('notes')->insertGetId([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'status' => $validated['status'] ?? Note::STATUS_DRAFT,
            'is_pinned' => $validated['is_pinned'] ?? false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->syncNoteCategories($noteId, $validated['category_ids'] ?? []);

        return response()->json([
            'message' => 'Poznámka bola úspešne vytvorená.',
            'note' => $this->hydrateNote($noteId),
        ], Response::HTTP_CREATED);
    }

    public function show(string $note)
    {
        $hydratedNote = $this->hydrateNote((int) $note);

        if ($hydratedNote === null) {
            return $this->notFoundResponse('Poznámka nenájdená.');
        }

        return response()->json([
            'note' => $hydratedNote,
        ], Response::HTTP_OK);
    }

    public function update(Request $request, string $note)
    {
        $noteId = (int) $note;

        if ($this->findNote($noteId) === null) {
            return $this->notFoundResponse('Poznámka nenájdená.');
        }

        $validated = $request->validate([
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:128'],
            'body' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Note::STATUSES)],
            'is_pinned' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $updateData = Arr::except($validated, ['category_ids']);

        if ($updateData !== [] || array_key_exists('category_ids', $validated)) {
            $updateData['updated_at'] = now();
            DB::table('notes')->where('id', $noteId)->update($updateData);
        }

        if (array_key_exists('category_ids', $validated)) {
            $this->syncNoteCategories($noteId, $validated['category_ids']);
        }

        return response()->json([
            'message' => 'Poznámka bola úspešne aktualizovaná.',
            'note' => $this->hydrateNote($noteId),
        ], Response::HTTP_OK);
    }

    public function destroy(string $note)
    {
        $noteId = (int) $note;

        if ($this->findNote($noteId) === null) {
            return $this->notFoundResponse('Poznámka nenájdená.');
        }

        DB::table('notes')->where('id', $noteId)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Poznámka odstránená.',
        ], Response::HTTP_OK);
    }

    public function statsByStatus()
    {
        $stats = DB::table('notes')
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn (object $stat): array => [
                'status' => $stat->status,
                'count' => (int) $stat->count,
            ]);

        return response()->json(['stats' => $stats], Response::HTTP_OK);
    }

    public function archiveOldDrafts()
    {
        $affected = DB::table('notes')
            ->whereNull('deleted_at')
            ->where('status', Note::STATUS_DRAFT)
            ->where('updated_at', '<', now()->subDays(30))
            ->update([
                'status' => Note::STATUS_ARCHIVED,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Staré koncepty boli archivované.',
            'affected_rows' => $affected,
        ], Response::HTTP_OK);
    }

    public function userNotesWithCategories(string $userId)
    {
        $userExists = DB::table('users')->where('id', (int) $userId)->exists();

        if (! $userExists) {
            return $this->notFoundResponse('Používateľ nenájdený.');
        }

        $notes = DB::table('notes')
            ->whereNull('deleted_at')
            ->where('user_id', (int) $userId)
            ->orderByDesc('updated_at')
            ->get();

        $mappedNotes = $this->hydrateNotes($notes)->map(fn (array $note): array => [
            'id' => $note['id'],
            'title' => $note['title'],
            'status' => $note['status'],
            'is_pinned' => $note['is_pinned'],
            'categories' => array_map(
                fn (array $category): string => $category['name'],
                $note['categories']
            ),
        ]);

        return response()->json(['notes' => $mappedNotes], Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = DB::table('notes')
            ->whereNull('deleted_at')
            ->where('status', Note::STATUS_PUBLISHED);

        if ($q !== '') {
            $query->where(function ($nestedQuery) use ($q): void {
                $nestedQuery
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%");
            });
        }

        $notes = $query
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return response()->json([
            'query' => $q,
            'notes' => $this->hydrateNotes($notes)->values(),
        ], Response::HTTP_OK);
    }

    public function pin(string $note)
    {
        return $this->updateNoteState((int) $note, ['is_pinned' => true], 'Poznámka bola pripnutá.');
    }

    public function unpin(string $note)
    {
        return $this->updateNoteState((int) $note, ['is_pinned' => false], 'Poznámka bola odopnutá.');
    }

    public function publish(string $note)
    {
        return $this->updateNoteState((int) $note, ['status' => Note::STATUS_PUBLISHED], 'Poznámka bola publikovaná.');
    }

    public function archive(string $note)
    {
        return $this->updateNoteState((int) $note, ['status' => Note::STATUS_ARCHIVED], 'Poznámka bola archivovaná.');
    }

    private function updateNoteState(int $noteId, array $attributes, string $message)
    {
        if ($this->findNote($noteId) === null) {
            return $this->notFoundResponse('Poznámka nenájdená.');
        }

        $attributes['updated_at'] = now();

        DB::table('notes')->where('id', $noteId)->update($attributes);

        return response()->json([
            'message' => $message,
            'note' => $this->hydrateNote($noteId),
        ], Response::HTTP_OK);
    }

    private function findNote(int $noteId): ?object
    {
        return DB::table('notes')
            ->whereNull('deleted_at')
            ->where('id', $noteId)
            ->first();
    }

    private function hydrateNote(int $noteId): ?array
    {
        $note = $this->findNote($noteId);

        if ($note === null) {
            return null;
        }

        return $this->hydrateNotes(collect([$note]))->first();
    }

    private function hydrateNotes(Collection $notes): Collection
    {
        if ($notes->isEmpty()) {
            return collect();
        }

        $userIds = $notes->pluck('user_id')->unique()->values();
        $noteIds = $notes->pluck('id')->values();

        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $categoriesByNoteId = DB::table('note_category')
            ->join('categories', 'categories.id', '=', 'note_category.category_id')
            ->whereIn('note_category.note_id', $noteIds)
            ->orderBy('categories.name')
            ->get([
                'note_category.note_id',
                'categories.id',
                'categories.name',
                'categories.created_at',
                'categories.updated_at',
            ])
            ->groupBy('note_id')
            ->map(fn (Collection $categories): array => $categories
                ->map(fn (object $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ])
                ->values()
                ->all());

        return $notes->map(function (object $note) use ($users, $categoriesByNoteId): array {
            $user = $users->get($note->user_id);

            return [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'title' => $note->title,
                'body' => $note->body,
                'status' => $note->status,
                'is_pinned' => (bool) $note->is_pinned,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
                'deleted_at' => $note->deleted_at,
                'user' => $user ? [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'premium_until' => $user->premium_until,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null,
                'categories' => $categoriesByNoteId->get($note->id, []),
            ];
        });
    }

    private function syncNoteCategories(int $noteId, array $categoryIds): void
    {
        DB::table('note_category')->where('note_id', $noteId)->delete();

        if ($categoryIds === []) {
            return;
        }

        $now = now();

        DB::table('note_category')->insert(
            collect($categoryIds)
                ->unique()
                ->values()
                ->map(fn (int $categoryId): array => [
                    'note_id' => $noteId,
                    'category_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );
    }

    private function notFoundResponse(string $message)
    {
        return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
    }
}
