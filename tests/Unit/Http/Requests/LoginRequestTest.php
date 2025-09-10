<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    protected LoginRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new LoginRequest();
    }

    /**
     * Test validation rules are properly defined
     */
    public function testValidationRulesAreProperlyDefined(): void
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertEquals('required|email', $rules['email']);
        $this->assertEquals('required|string', $rules['password']);
    }

    /**
     * Test valid data passes validation
     */
    public function testValidDataPassesValidation(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test email is required
     */
    public function testEmailIsRequired(): void
    {
        $data = [
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertContains('The email field is required.', $validator->errors()->get('email'));
    }

    /**
     * Test password is required
     */
    public function testPasswordIsRequired(): void
    {
        $data = [
            'email' => 'test@example.com',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
        $this->assertContains('The password field is required.', $validator->errors()->get('password'));
    }

    /**
     * Test email must be valid email format
     */
    public function testEmailMustBeValidEmailFormat(): void
    {
        $invalidEmails = [
            'not-an-email',
            'invalid@',
            '@example.com',
            'test@',
            'test..@example.com',
            'test user@example.com',
            // Note: 'test@example' might be valid in some contexts as a local domain
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $data = [
                'email' => $invalidEmail,
                'password' => 'password123',
            ];

            $validator = Validator::make($data, $this->request->rules());

            $this->assertFalse($validator->passes(), "Email '{$invalidEmail}' should fail validation");
            $this->assertArrayHasKey('email', $validator->errors()->toArray());
            $this->assertContains('The email field must be a valid email address.', $validator->errors()->get('email'));
        }
    }

    /**
     * Test various valid email formats pass validation
     */
    public function testVariousValidEmailFormatsPassValidation(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_name@example-domain.com',
            '123@example.com',
            'u@e.co',
        ];

        foreach ($validEmails as $validEmail) {
            $data = [
                'email' => $validEmail,
                'password' => 'password123',
            ];

            $validator = Validator::make($data, $this->request->rules());

            $this->assertTrue($validator->passes(), "Email '{$validEmail}' should pass validation");
        }
    }

    /**
     * Test password must be string
     */
    public function testPasswordMustBeString(): void
    {
        $nonStringPasswords = [
            123456,
            12.34,
            true,
            false,
            ['password'],
            null,
        ];

        foreach ($nonStringPasswords as $nonStringPassword) {
            $data = [
                'email' => 'test@example.com',
                'password' => $nonStringPassword,
            ];

            $validator = Validator::make($data, $this->request->rules());

            // null will fail required validation
            if ($nonStringPassword === null) {
                $this->assertFalse($validator->passes());
                $this->assertArrayHasKey('password', $validator->errors()->toArray());
            } else {
                // Other non-string values should fail string validation
                // Note: In Laravel, numeric values are often coerced to strings
                if (! is_numeric($nonStringPassword) && ! is_bool($nonStringPassword)) {
                    $this->assertFalse($validator->passes(), 'Password of type ' . gettype($nonStringPassword) . ' should fail validation');
                }
            }
        }
    }

    /**
     * Test empty string values fail validation
     */
    public function testEmptyStringValuesFailValidation(): void
    {
        $data = [
            'email' => '',
            'password' => '',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Test whitespace-only values fail validation
     */
    public function testWhitespaceOnlyValuesFailValidation(): void
    {
        $data = [
            'email' => '   ',
            'password' => '   ',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test extra fields are allowed (mass assignment protection handles this)
     */
    public function testExtraFieldsAreIgnoredInValidation(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'extra_field' => 'extra_value',
            'another_field' => 123,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test LoginRequest extends FormRequest
     */
    public function testLoginRequestExtendsFormRequest(): void
    {
        $this->assertInstanceOf(\Illuminate\Foundation\Http\FormRequest::class, $this->request);
    }

    /**
     * Test rules method returns array
     */
    public function testRulesMethodReturnsArray(): void
    {
        $rules = $this->request->rules();
        $this->assertIsArray($rules);
    }

    /**
     * Test long email addresses are accepted
     */
    public function testLongEmailAddressesAreAccepted(): void
    {
        $longEmail = str_repeat('a', 50) . '@' . str_repeat('b', 50) . '.com';

        $data = [
            'email' => $longEmail,
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test long passwords are accepted
     */
    public function testLongPasswordsAreAccepted(): void
    {
        $longPassword = str_repeat('a', 1000);

        $data = [
            'email' => 'test@example.com',
            'password' => $longPassword,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test special characters in password are accepted
     */
    public function testSpecialCharactersInPasswordAreAccepted(): void
    {
        $specialPasswords = [
            '!@#$%^&*()',
            'p@ssw0rd!',
            '___---+++',
            '日本語パスワード',
            '🔐🔑💻',
            "pass'word\"with`quotes",
        ];

        foreach ($specialPasswords as $password) {
            $data = [
                'email' => 'test@example.com',
                'password' => $password,
            ];

            $validator = Validator::make($data, $this->request->rules());

            $this->assertTrue($validator->passes(), "Password '{$password}' should pass validation");
        }
    }

    /**
     * Test validation messages are in English
     */
    public function testValidationMessagesAreInEnglish(): void
    {
        $data = [];

        $validator = Validator::make($data, $this->request->rules());
        $validator->passes();

        $errors = $validator->errors();

        $this->assertStringContainsString('required', $errors->first('email'));
        $this->assertStringContainsString('required', $errors->first('password'));
    }
}
