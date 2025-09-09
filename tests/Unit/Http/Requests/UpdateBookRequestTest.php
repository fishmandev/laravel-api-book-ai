<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateBookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateBookRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->request = new UpdateBookRequest();
    }

    /**
     * Helper method to validate data against request rules
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->request->rules());
    }

    /**
     * Test valid data with both fields passes validation
     */
    public function test_valid_data_with_both_fields_passes_validation(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Book Title',
            'description' => 'This is an updated book description.'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test updating only title is valid
     */
    public function test_updating_only_title_is_valid(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Title Only'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test updating only description is valid
     */
    public function test_updating_only_description_is_valid(): void
    {
        // Arrange
        $data = [
            'description' => 'Updated description only'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test empty request is valid (no fields required)
     */
    public function test_empty_request_is_valid(): void
    {
        // Arrange
        $data = [];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test title must be a string when provided
     */
    public function test_title_must_be_string_when_provided(): void
    {
        // Arrange
        $data = [
            'title' => 12345
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertContains('The title field must be a string.', $validator->errors()->get('title'));
    }

    /**
     * Test description must be a string when provided
     */
    public function test_description_must_be_string_when_provided(): void
    {
        // Arrange
        $data = [
            'description' => ['array', 'not', 'string']
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
    public function test_title_cannot_exceed_255_characters(): void
    {
        // Arrange
        $data = [
            'title' => str_repeat('a', 256)
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
    public function test_title_with_exactly_255_characters_is_valid(): void
    {
        // Arrange
        $data = [
            'title' => str_repeat('a', 255)
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test empty string for title is invalid (required when present)
     */
    public function test_empty_string_title_is_invalid(): void
    {
        // Arrange
        $data = [
            'title' => ''
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test empty string for description is invalid (required when present)
     */
    public function test_empty_string_description_is_invalid(): void
    {
        // Arrange
        $data = [
            'description' => ''
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /**
     * Test null value for title is invalid when field is present
     */
    public function test_null_title_is_invalid_when_present(): void
    {
        // Arrange
        $data = [
            'title' => null,
            'description' => 'Valid description'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test null value for description is invalid when field is present
     */
    public function test_null_description_is_invalid_when_present(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'description' => null
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /**
     * Test whitespace-only title is invalid
     */
    public function test_whitespace_only_title_is_invalid(): void
    {
        // Arrange
        $data = [
            'title' => '   '
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * Test whitespace-only description is invalid
     */
    public function test_whitespace_only_description_is_invalid(): void
    {
        // Arrange
        $data = [
            'description' => '   '
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
    }

    /**
     * Test very long description is valid (no max limit)
     */
    public function test_very_long_description_is_valid(): void
    {
        // Arrange
        $data = [
            'description' => str_repeat('Lorem ipsum dolor sit amet. ', 1000)
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test rules method returns expected array structure
     */
    public function test_rules_method_returns_expected_structure(): void
    {
        // Act
        $rules = $this->request->rules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertEquals('sometimes|required|string|max:255', $rules['title']);
        $this->assertEquals('sometimes|required|string', $rules['description']);
    }

    /**
     * Test additional fields are ignored
     */
    public function test_additional_fields_are_ignored(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'extra_field' => 'This should be ignored',
            'another_field' => 123
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $validated = $validator->validated();
        $this->assertArrayNotHasKey('extra_field', $validated);
        $this->assertArrayNotHasKey('another_field', $validated);
        $this->assertCount(1, $validated);
        $this->assertArrayHasKey('title', $validated);
    }

    /**
     * Test special characters in title and description are valid
     */
    public function test_special_characters_are_valid(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Title: !@#$%^&*()_+-=[]{}|;\':",.<>?/`~',
            'description' => 'Updated description: éàüöß 中文 العربية emoji 😀'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test numeric string values are valid
     */
    public function test_numeric_string_values_are_valid(): void
    {
        // Arrange
        $data = [
            'title' => '12345',
            'description' => '67890'
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test sometimes rule allows field to be completely absent
     */
    public function test_sometimes_rule_allows_fields_to_be_absent(): void
    {
        // Arrange - No data at all
        $data = [];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->validated());
    }

    /**
     * Test difference between sometimes and required validation
     */
    public function test_difference_between_sometimes_and_required(): void
    {
        // Test 1: Field present but empty - should fail
        $data1 = ['title' => ''];
        $validator1 = $this->validate($data1);
        $this->assertFalse($validator1->passes());

        // Test 2: Field not present at all - should pass
        $data2 = [];
        $validator2 = $this->validate($data2);
        $this->assertTrue($validator2->passes());

        // Test 3: Field present with value - should pass
        $data3 = ['title' => 'Valid Title'];
        $validator3 = $this->validate($data3);
        $this->assertTrue($validator3->passes());
    }
}