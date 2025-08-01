<?php

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Response;

it('allows authenticated user to list their todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create todos for both users
    Todo::factory()->create(['user_id' => $user->id, 'title' => 'User todo 1']);
    Todo::factory()->create(['user_id' => $user->id, 'title' => 'User todo 2']);
    Todo::factory()->create(['user_id' => $otherUser->id, 'title' => 'Other user todo']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/todos');

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonFragment(['title' => 'User todo 1']);
    $response->assertJsonFragment(['title' => 'User todo 2']);
    $response->assertJsonMissing(['title' => 'Other user todo']);
});

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

    $response->assertStatus(204);
    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});

it('does not allow user to delete others\' todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/todos/{$todo->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('todos', ['id' => $todo->id]);
});

it('allows authenticated user to mark their todo as completed', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['user_id' => $user->id, 'completed' => false]);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/todos/{$todo->id}", [
        'completed' => true,
        'title' => $todo->title, // keep title unchanged
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'completed' => true,
    ]);
});

