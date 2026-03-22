<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_managed_through_the_api(): void
    {
        Category::factory()->create(['name' => 'Skola']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Skola');

        $storeResponse = $this->postJson('/api/categories', [
            'name' => 'Praca',
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('category.name', 'Praca');

        $categoryId = $storeResponse->json('category.id');

        $this->getJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('category.name', 'Praca');

        $this->putJson("/api/categories/{$categoryId}", [
            'name' => 'Osobne',
        ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Osobne');

        $this->deleteJson("/api/categories/{$categoryId}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }
}
