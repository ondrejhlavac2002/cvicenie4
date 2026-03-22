<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_can_be_created_with_their_relations(): void
    {
        $user = User::factory()->create();
        $categories = Category::factory()->count(2)->create();

        $response = $this->postJson('/api/notes', [
            'user_id' => $user->id,
            'title' => 'Laravel assignment',
            'body' => 'Implement Eloquent controllers.',
            'status' => Note::STATUS_DRAFT,
            'is_pinned' => true,
            'category_ids' => $categories->pluck('id')->all(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('note.title', 'Laravel assignment')
            ->assertJsonPath('note.user.id', $user->id)
            ->assertJsonCount(2, 'note.categories');

        $noteId = $response->json('note.id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'title' => 'Laravel assignment',
            'status' => Note::STATUS_DRAFT,
            'is_pinned' => true,
        ]);

        foreach ($categories as $category) {
            $this->assertDatabaseHas('note_category', [
                'note_id' => $noteId,
                'category_id' => $category->id,
            ]);
        }
    }

    public function test_note_actions_can_pin_publish_unpin_and_archive_a_note(): void
    {
        $note = Note::factory()->draft()->create([
            'is_pinned' => false,
        ]);

        $this->patchJson("/api/notes/{$note->id}/pin")
            ->assertOk()
            ->assertJsonPath('note.is_pinned', true);

        $this->patchJson("/api/notes/{$note->id}/publish")
            ->assertOk()
            ->assertJsonPath('note.status', Note::STATUS_PUBLISHED);

        $this->patchJson("/api/notes/{$note->id}/unpin")
            ->assertOk()
            ->assertJsonPath('note.is_pinned', false);

        $this->patchJson("/api/notes/{$note->id}/archive")
            ->assertOk()
            ->assertJsonPath('note.status', Note::STATUS_ARCHIVED);
    }

    public function test_search_and_status_stats_return_expected_results(): void
    {
        $matchingNote = Note::factory()->published()->create([
            'title' => 'Laravel API',
        ]);

        Note::factory()->draft()->create([
            'title' => 'Hidden draft',
        ]);

        $this->getJson('/api/notes-actions/search?q=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'notes')
            ->assertJsonPath('notes.0.id', $matchingNote->id);

        $this->getJson('/api/notes/stats/status')
            ->assertOk()
            ->assertJsonFragment([
                'status' => Note::STATUS_DRAFT,
                'count' => 1,
            ])
            ->assertJsonFragment([
                'status' => Note::STATUS_PUBLISHED,
                'count' => 1,
            ]);
    }
}
