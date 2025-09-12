<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookController
{
    /**
     * Display a listing of the resource.
     * GET /api/books - Paginated book list (10 per page)
     */
    public function index(): AnonymousResourceCollection
    {
        // Get paginated books
        $books = Book::paginate(10);

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/books - Create new book
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        // Create book with validated data
        $book = Book::create($request->validated());

        return (new BookResource($book))
            ->additional([
                'message' => 'Book created successfully',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     * GET /api/books/{book} - Get book details
     */
    public function show(Book $book): BookResource
    {

        return new BookResource($book);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/books/{book} - Edit book
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        // Update book with validated data
        $book->update($request->validated());

        return (new BookResource($book->fresh()))
            ->additional([
                'message' => 'Book updated successfully',
            ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/books/{book} - Delete book
     */
    public function destroy(Book $book): JsonResponse
    {
        // Delete book object
        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully',
        ]);
    }
}
