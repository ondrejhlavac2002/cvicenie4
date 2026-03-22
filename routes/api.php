<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::get('notes/stats/status', [NoteController::class, 'statsByStatus']);
Route::patch('notes/actions/archive-old-drafts', [NoteController::class, 'archiveOldDrafts']);
Route::get('users/{userId}/notes', [NoteController::class, 'userNotesWithCategories']);
Route::get('notes-actions/search', [NoteController::class, 'search']);
Route::patch('notes/{note}/pin', [NoteController::class, 'pin']);
Route::patch('notes/{note}/unpin', [NoteController::class, 'unpin']);
Route::patch('notes/{note}/publish', [NoteController::class, 'publish']);
Route::patch('notes/{note}/archive', [NoteController::class, 'archive']);

Route::apiResource('notes', NoteController::class);
Route::apiResource('categories', CategoryController::class);
