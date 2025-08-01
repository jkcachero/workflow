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

