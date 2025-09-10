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
     * Test valid data with both fields passes validation
     */
    public function testValidDataWithBothFieldsPassesValidation(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Book Title',
            'description' => 'This is an updated book description.',
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
    public function testUpdatingOnlyTitleIsValid(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Title Only',
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
    public function testUpdatingOnlyDescriptionIsValid(): void
    {
        // Arrange
        $data = [
            'description' => 'Updated description only',
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
    public function testEmptyRequestIsValid(): void
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
    public function testTitleMustBeStringWhenProvided(): void
    {
        // Arrange
        $data = [
            'title' => 12345,
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
    public function testDescriptionMustBeStringWhenProvided(): void
    {
        // Arrange
        $data = [
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
        ];

        // Act
        $validator = $this->validate($data);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * Test empty string for title is invalid (required when present)
     */
    public function testEmptyStringTitleIsInvalid(): void
    {
        // Arrange
        $data = [
            'title' => '',
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
    public function testEmptyStringDescriptionIsInvalid(): void
    {
        // Arrange
        $data = [
            'description' => '',
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
    public function testNullTitleIsInvalidWhenPresent(): void
    {
        // Arrange
        $data = [
            'title' => null,
            'description' => 'Valid description',
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
    public function testNullDescriptionIsInvalidWhenPresent(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
            'description' => null,
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
    public function testWhitespaceOnlyTitleIsInvalid(): void
    {
        // Arrange
        $data = [
            'title' => '   ',
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
    public function testWhitespaceOnlyDescriptionIsInvalid(): void
    {
        // Arrange
        $data = [
            'description' => '   ',
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
    public function testVeryLongDescriptionIsValid(): void
    {
        // Arrange
        $data = [
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
        $this->assertEquals('sometimes|required|string|max:255', $rules['title']);
        $this->assertEquals('sometimes|required|string', $rules['description']);
    }

    /**
     * Test additional fields are ignored
     */
    public function testAdditionalFieldsAreIgnored(): void
    {
        // Arrange
        $data = [
            'title' => 'Valid title',
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
        $this->assertCount(1, $validated);
        $this->assertArrayHasKey('title', $validated);
    }

    /**
     * Test special characters in title and description are valid
     */
    public function testSpecialCharactersAreValid(): void
    {
        // Arrange
        $data = [
            'title' => 'Updated Title: !@#$%^&*()_+-=[]{}|;\':",.<>?/`~',
            'description' => 'Updated description: éàüöß 中文 العربية emoji 😀',
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
     * Test sometimes rule allows field to be completely absent
     */
    public function testSometimesRuleAllowsFieldsToBeAbsent(): void
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
    public function testDifferenceBetweenSometimesAndRequired(): void
    {
        // Test 1: Field present but empty - should fail
        $data1 = [
            'title' => '',
        ];
        $validator1 = $this->validate($data1);
        $this->assertFalse($validator1->passes());

        // Test 2: Field not present at all - should pass
        $data2 = [];
        $validator2 = $this->validate($data2);
        $this->assertTrue($validator2->passes());

        // Test 3: Field present with value - should pass
        $data3 = [
            'title' => 'Valid Title',
        ];
        $validator3 = $this->validate($data3);
        $this->assertTrue($validator3->passes());
    }

    /**
     * Helper method to validate data against request rules
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->request->rules());
    }
}
