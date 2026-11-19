<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($request)
    {
        $books = Book::with('category')
            ->when($request->query('q'), fn ($q, $term) => $q->where(
                fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('author', 'like', "%{$term}%")
            ))
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->paginate(15);

        return BookResource::collection($books);
    }

    public function store(Request $request)
    {
        return BookResource::make(Book::create($request->validated())->load('category'))
            ->response()->setStatusCode(201);
    }

    public function show(Book $book)
    {
        return BookResource::make($book->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        $book->update($request->validated());

        return BookResource::make($book->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        if ($book->loans()->exists()) {
            return response()->json(['message' => 'Book has loan history and cannot be deleted.'], 409);
        }

        $book->delete();

        return response()->noContent();
    }
}
