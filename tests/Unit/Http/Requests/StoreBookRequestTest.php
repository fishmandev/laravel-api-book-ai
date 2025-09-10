<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreBookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private StoreBookRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new StoreBookRequest();
    }

    /**
     * Test valid data passes validation
     */
    public function testValidDataPassesValidation(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid Book Title',
            'description' => 'This is a valid book description.',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test title is required
     */
    public function testTitleIsRequired(): void
    {
        // Arrange
        $data = [
            'description' => 'Book description without title',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertContains('The title field is required.', $validator->errors()->get('title'));
    }

    /**
     * Test description is required
     */
    public function testDescriptionIsRequired(): void
    {
        // Arrange
        $data = [
            'title' => 'Book title without description',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertContains('The description field is required.', $validator->errors()->get('description'));
    }

    /**
     * Test both title and description are required
     */
    public function testBothTitleAndDescriptionAreRequired(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertCount(2, $validator->errors()->all());
    }

    /**
     * Test title must be a string
     */
    public function testTitleMustBeString(): void
    {
        // Arrange
        $data = [
            'title' => 12345,
            'description' => 'Valid description',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertContains('The title field must be a string.', $validator->errors()->get('title'));
    }

    /**
     * Test description must be a string
     */
    public function testDescriptionMustBeString(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'description' => ['array', 'not', 'string'],
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertContains('The description field must be a string.', $validator->errors()->get('description'));
    }

    /**
     * Test title cannot exceed 255 characters
     */
    public function testTitleCannotExceed255Characters(): void
    {
        // Arrange
        $data = [
            'title' => str_repeat('a', 256),
            'description' => 'Valid description',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertContains('The title field must not be greater than 255 characters.', $validator->errors()->get('title'));
    }

    /**
     * Test title with exactly 255 characters is valid
     */
    public function testTitleWithExactly255CharactersIsValid(): void
    {
        // Arrange
        $data = [
            'title' => str_repeat('a', 255),
            'description' => 'Valid description',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test empty string values are invalid
     */
    public function testEmptyStringValuesAreInvalid(): void
    {
        // Arrange
        $data = [
            'title' => '',
            'description' => '',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /**
     * Test null values are invalid
     */
    public function testNullValuesAreInvalid(): void
    {
        // Arrange
        $data = [
            'title' => null,
            'description' => null,
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /**
     * Test whitespace-only title is invalid
     */
    public function testWhitespaceOnlyTitleIsInvalid(): void
    {
        // Arrange
        $data = [
            'title' => '   ',
            'description' => 'Valid description',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test very long description is valid (no max limit)
     */
    public function testVeryLongDescriptionIsValid(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'description' => str_repeat('Lorem ipsum dolor sit amet. ', 1000),
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test rules method returns expected array structure
     */
    public function testRulesMethodReturnsExpectedStructure(): void
    {
        // Act
        $rules = $this->request->rules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertEquals('required|string|max:255', $rules['title']);
        $this->assertEquals('required|string', $rules['description']);
    }

    /**
     * Test additional fields are ignored
     */
    public function testAdditionalFieldsAreIgnored(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'description' => 'Valid description',
            'extra_field' => 'This should be ignored',
            'another_field' => 123,
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $validated = $validator->validated();
        $this->assertArrayNotHasKey('extra_field', $validated);
        $this->assertArrayNotHasKey('another_field', $validated);
        $this->assertCount(2, $validated);
    }

    /**
     * Test special characters in title and description are valid
     */
    public function testSpecialCharactersAreValid(): void
    {
        // Arrange
        $data = [
            'title' => 'Book Title with Special Characters: !@#$%^&*()_+-=[]{}|;\':",.<>?/`~',
            'description' => 'Description with special chars: éàüöß 中文 العربية emoji 😀',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test numeric string values are valid
     */
    public function testNumericStringValuesAreValid(): void
    {
        // Arrange
        $data = [
            'title' => '12345',
            'description' => '67890',
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Helper method to validate data against request rules
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->request->rules());
    }
}
