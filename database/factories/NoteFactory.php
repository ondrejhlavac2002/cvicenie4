<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(Note::STATUSES),
            'is_pinned' => fake()->boolean(20),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Note::STATUS_DRAFT]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Note::STATUS_PUBLISHED]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => Note::STATUS_ARCHIVED]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
