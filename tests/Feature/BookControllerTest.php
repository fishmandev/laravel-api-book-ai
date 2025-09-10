<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication (ensure ID is not 1 to avoid system admin bypass)
        $this->user = User::factory()->create([
            'id' => 2,
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /**
     * Test index method - successful listing with pagination
     */
    public function testIndexReturnsPaginatedBooksWithPermission(): void
    {
        // Arrange
        $this->giveUserPermission('books.list');
        Book::factory()->count(15)->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson('/api/v1/books');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10);
    }

    /**
     * Test index method - unauthorized without permission
     */
    public function testIndexReturns403WithoutPermission(): void
    {
        // Arrange
        Book::factory()->count(5)->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson('/api/v1/books');

        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * Test index method - unauthenticated request
     */
    public function testIndexReturns401WhenUnauthenticated(): void
    {
        // Act
        $response = $this->getJson('/api/v1/books');

        // Assert
        $response->assertUnauthorized();
    }

    /**
     * Test store method - successful creation with valid data
     */
    public function testStoreCreatesBookWithValidDataAndPermission(): void
    {
        // Arrange
        $this->giveUserPermission('books.create');
        $bookData = [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                'message',
            ])
            ->assertJsonPath('data.title', $bookData['title'])
            ->assertJsonPath('data.description', $bookData['description'])
            ->assertJsonPath('message', 'Book created successfully');

        $this->assertDatabaseHas('books', $bookData);
    }

    /**
     * Test store method - validation error with missing title
     */
    public function testStoreFailsValidationWithoutTitle(): void
    {
        // Arrange
        $this->giveUserPermission('books.create');
        $bookData = [
            'description' => $this->faker->paragraph(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test store method - validation error with missing description
     */
    public function testStoreFailsValidationWithoutDescription(): void
    {
        // Arrange
        $this->giveUserPermission('books.create');
        $bookData = [
            'title' => $this->faker->sentence(3),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    /**
     * Test store method - validation error with title too long
     */
    public function testStoreFailsValidationWithTitleTooLong(): void
    {
        // Arrange
        $this->giveUserPermission('books.create');
        $bookData = [
            'title' => str_repeat('a', 256),
            'description' => $this->faker->paragraph(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test store method - unauthorized without permission
     */
    public function testStoreReturns403WithoutPermission(): void
    {
        // Arrange
        $bookData = [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * Test show method - successful retrieval with permission
     */
    public function testShowReturnsBookWithPermission(): void
    {
        // Arrange
        $this->giveUserPermission('books.view');
        $book = Book::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.description', $book->description);
    }

    /**
     * Test show method - 404 with non-existent book
     */
    public function testShowReturns404ForNonExistentBook(): void
    {
        // Arrange
        $this->giveUserPermission('books.view');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson('/api/v1/books/999999');

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test show method - unauthorized without permission
     */
    public function testShowReturns403WithoutPermission(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * Test update method - successful update with valid data
     */
    public function testUpdateModifiesBookWithValidDataAndPermission(): void
    {
        // Arrange
        $this->giveUserPermission('books.edit');
        $book = Book::factory()->create();
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$book->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                'message',
            ])
            ->assertJsonPath('data.title', $updateData['title'])
            ->assertJsonPath('data.description', $updateData['description'])
            ->assertJsonPath('message', 'Book updated successfully');

        $this->assertDatabaseHas('books', array_merge([
            'id' => $book->id,
        ], $updateData));
    }

    /**
     * Test update method - partial update with only title
     */
    public function testUpdateAllowsPartialUpdateWithOnlyTitle(): void
    {
        // Arrange
        $this->giveUserPermission('books.edit');
        $book = Book::factory()->create();
        $originalDescription = $book->description;
        $updateData = [
            'title' => 'New Title Only',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$book->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.title', $updateData['title'])
            ->assertJsonPath('data.description', $originalDescription);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $updateData['title'],
            'description' => $originalDescription,
        ]);
    }

    /**
     * Test update method - partial update with only description
     */
    public function testUpdateAllowsPartialUpdateWithOnlyDescription(): void
    {
        // Arrange
        $this->giveUserPermission('books.edit');
        $book = Book::factory()->create();
        $originalTitle = $book->title;
        $updateData = [
            'description' => 'New Description Only',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$book->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.title', $originalTitle)
            ->assertJsonPath('data.description', $updateData['description']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $originalTitle,
            'description' => $updateData['description'],
        ]);
    }

    /**
     * Test update method - validation error with title too long
     */
    public function testUpdateFailsValidationWithTitleTooLong(): void
    {
        // Arrange
        $this->giveUserPermission('books.edit');
        $book = Book::factory()->create();
        $updateData = [
            'title' => str_repeat('a', 256),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$book->id}", $updateData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test update method - unauthorized without permission
     */
    public function testUpdateReturns403WithoutPermission(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $updateData = [
            'title' => 'Updated Title',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$book->id}", $updateData);

        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * Test update method - 404 with non-existent book
     */
    public function testUpdateReturns404ForNonExistentBook(): void
    {
        // Arrange
        $this->giveUserPermission('books.edit');
        $updateData = [
            'title' => 'Updated Title',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson('/api/v1/books/999999', $updateData);

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test destroy method - successful deletion with permission
     */
    public function testDestroyDeletesBookWithPermission(): void
    {
        // Arrange
        $this->giveUserPermission('books.delete');
        $book = Book::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->deleteJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Book deleted successfully',
            ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * Test destroy method - unauthorized without permission
     */
    public function testDestroyReturns403WithoutPermission(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->deleteJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * Test destroy method - 404 with non-existent book
     */
    public function testDestroyReturns404ForNonExistentBook(): void
    {
        // Arrange
        $this->giveUserPermission('books.delete');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->deleteJson('/api/v1/books/999999');

        // Assert
        $response->assertNotFound();
    }

    /**
     * Test multiple permissions - user with all book permissions
     */
    public function testUserWithAllPermissionsCanPerformAllActions(): void
    {
        // Arrange
        $permissions = ['books.list', 'books.create', 'books.view', 'books.edit', 'books.delete'];
        foreach ($permissions as $permission) {
            $this->giveUserPermission($permission);
        }

        // Test Create
        $createData = [
            'title' => 'Test Book',
            'description' => 'Test Description',
        ];
        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->postJson('/api/v1/books', $createData);
        $createResponse->assertCreated();
        $bookId = $createResponse->json('data.id');

        // Test List
        $listResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson('/api/v1/books');
        $listResponse->assertOk();

        // Test View
        $viewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->getJson("/api/v1/books/{$bookId}");
        $viewResponse->assertOk();

        // Test Update
        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->putJson("/api/v1/books/{$bookId}", [
                'title' => 'Updated Title',
            ]);
        $updateResponse->assertOk();

        // Test Delete
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])
            ->deleteJson("/api/v1/books/{$bookId}");
        $deleteResponse->assertOk();
    }

    /**
     * Helper method to give user a permission
     */
    private function giveUserPermission(string $permissionName): void
    {
        $permission = Permission::firstOrCreate([
            'name' => $permissionName,
        ]);
        $role = Role::firstOrCreate([
            'name' => 'test-role',
        ]);
        $role->permissions()->syncWithoutDetaching($permission);
        $this->user->roles()->syncWithoutDetaching($role);

        // Re-define gates after creating permissions
        PermissionService::defineGates();
    }
}
