<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $todos = $request->user()->todos()->paginate(10);

        if ($request->wantsJson()) {
            return response()->json($todos);
        }

        return Inertia::render('Todos', ['todos' => $todos]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todo = $request->user()->todos()->create($data);

        if ($request->wantsJson()) {
            return response()->json($todo, Response::HTTP_CREATED);
        }

        return redirect()->route('todos.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Todo $todo)
    {
        if ($request->user()->id !== $todo->user_id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'completed' => 'sometimes|boolean',
        ]);

        $todo->update($data);

        if ($request->wantsJson()) {
            return response()->json($todo, Response::HTTP_OK);
        }

        return redirect()->route('todos.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Todo $todo)
    {
        if ($request->user()->id !== $todo->user_id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $todo->delete();

        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return redirect()->route('todos.index');
    }
}

