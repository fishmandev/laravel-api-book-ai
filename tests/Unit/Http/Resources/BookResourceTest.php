<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BookResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test BookResource transforms model correctly
     */
    public function test_book_resource_transforms_model_correctly(): void
    {
        // Arrange
        $book = Book::factory()->create([
            'title' => 'Test Book Title',
            'description' => 'Test Book Description'
        ]);
        $request = Request::create('/api/v1/books/' . $book->id, 'GET');

        // Act
        $resource = new BookResource($book);
        $response = $resource->toArray($request);

        // Assert
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('description', $response);
        $this->assertArrayHasKey('created_at', $response);
        $this->assertArrayHasKey('updated_at', $response);
        
        $this->assertEquals($book->id, $response['id']);
        $this->assertEquals('Test Book Title', $response['title']);
        $this->assertEquals('Test Book Description', $response['description']);
        $this->assertEquals($book->created_at, $response['created_at']);
        $this->assertEquals($book->updated_at, $response['updated_at']);
    }

    /**
     * Test BookResource only includes specified fields
     */
    public function test_book_resource_only_includes_specified_fields(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $request = Request::create('/api/v1/books/' . $book->id, 'GET');

        // Act
        $resource = new BookResource($book);
        $response = $resource->toArray($request);

        // Assert
        $expectedKeys = ['id', 'title', 'description', 'created_at', 'updated_at'];
        $actualKeys = array_keys($response);
        
        $this->assertEquals($expectedKeys, $actualKeys);
        $this->assertCount(5, $response);
    }

    /**
     * Test BookResource handles empty description correctly
     */
    public function test_book_resource_handles_empty_description(): void
    {
        // Arrange
        $book = Book::factory()->create([
            'title' => 'Book with empty description',
            'description' => ''
        ]);
        $request = Request::create('/api/v1/books/' . $book->id, 'GET');

        // Act
        $resource = new BookResource($book);
        $response = $resource->toArray($request);

        // Assert
        $this->assertArrayHasKey('description', $response);
        $this->assertEquals('', $response['description']);
    }

    /**
     * Test BookResource collection transformation
     */
    public function test_book_resource_collection_transforms_multiple_books(): void
    {
        // Arrange
        $books = Book::factory()->count(3)->create();
        $request = Request::create('/api/v1/books', 'GET');

        // Act
        $collection = BookResource::collection($books);
        $response = $collection->toArray($request);

        // Assert
        $this->assertIsArray($response);
        $this->assertCount(3, $response);
        
        foreach ($response as $index => $bookData) {
            $this->assertArrayHasKey('id', $bookData);
            $this->assertArrayHasKey('title', $bookData);
            $this->assertArrayHasKey('description', $bookData);
            $this->assertArrayHasKey('created_at', $bookData);
            $this->assertArrayHasKey('updated_at', $bookData);
            
            $this->assertEquals($books[$index]->id, $bookData['id']);
            $this->assertEquals($books[$index]->title, $bookData['title']);
            $this->assertEquals($books[$index]->description, $bookData['description']);
        }
    }

    /**
     * Test BookResource with additional meta data
     */
    public function test_book_resource_can_include_additional_data(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $request = Request::create('/api/v1/books/' . $book->id, 'GET');

        // Act
        $resource = (new BookResource($book))
            ->additional(['message' => 'Book retrieved successfully']);
        
        $response = $resource->response($request)->getData(true);

        // Assert
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Book retrieved successfully', $response['message']);
        
        $bookData = $response['data'];
        $this->assertEquals($book->id, $bookData['id']);
        $this->assertEquals($book->title, $bookData['title']);
    }

    /**
     * Test BookResource preserves Carbon date instances
     */
    public function test_book_resource_preserves_carbon_date_instances(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $request = Request::create('/api/v1/books/' . $book->id, 'GET');

        // Act
        $resource = new BookResource($book);
        $response = $resource->toArray($request);

        // Assert
        $this->assertInstanceOf(\Carbon\Carbon::class, $response['created_at']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $response['updated_at']);
        $this->assertTrue($response['created_at']->equalTo($book->created_at));
        $this->assertTrue($response['updated_at']->equalTo($book->updated_at));
    }

    /**
     * Test BookResource handles empty collection
     */
    public function test_book_resource_handles_empty_collection(): void
    {
        // Arrange
        $books = Book::whereRaw('1 = 0')->get(); // Empty collection
        $request = Request::create('/api/v1/books', 'GET');

        // Act
        $collection = BookResource::collection($books);
        $response = $collection->toArray($request);

        // Assert
        $this->assertIsArray($response);
        $this->assertEmpty($response);
    }
}