<?php

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Response;

it('does not allow unauthenticated user to list todos', function () {
    $response = $this->getJson('/api/todos');

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

it('allows authenticated user to update their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Old title']);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'title' => 'Updated title',
    ]);

    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated title',
    ]);
});

it('does not allow user to update others\' todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $otherUser->id, 'title' => 'Other user todo']);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'title' => 'Hacked title',
    ]);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

it('validates title when updating a todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'title' => '',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors('title');
});

it('allows authenticated user to delete their todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});

it('does not allow user to delete others\' todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $this->assertDatabaseHas('todos', ['id' => $todo->id]);
});

it('allows authenticated user to mark their todo as completed', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id, 'completed' => false]);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'completed' => true,
        'title' => $todo->title,
    ]);

    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => true,
    ]);
});

it('allows authenticated user to list their todos with pagination', function () {
    $user = User::factory()->create();

    Todo::factory()->count(15)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/todos');

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure([
        'data',
        'links',
    ]);
    $this->assertCount(10, $response->json('data'));
});

it('allows authenticated user to edit their todo title', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Old title']);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'title' => 'New edited title',
        'completed' => (bool) $todo->completed,
    ]);

    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'New edited title',
    ]);
});

