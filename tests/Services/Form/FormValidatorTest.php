<?php

declare(strict_types=1);

namespace Tests\Services\Form;

use Architect\Services\Form\FormValidator;
use PHPUnit\Framework\TestCase;

class FormValidatorTest extends TestCase
{
    public function testRequiredValid(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['name' => 'John'], ['name' => 'required']));
    }

    public function testRequiredEmpty(): void
    {
        $validator = new FormValidator();
        $this->assertFalse($validator->validate(['name' => ''], ['name' => 'required']));
        $this->assertTrue($validator->hasErrors());
    }

    public function testRequiredNull(): void
    {
        $validator = new FormValidator();
        $this->assertFalse($validator->validate(['name' => null], ['name' => 'required']));
    }

    public function testRequiredMissing(): void
    {
        $validator = new FormValidator();
        $this->assertFalse($validator->validate([], ['name' => 'required']));
    }

    public function testEmailValid(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['email' => 'user@example.com'], ['email' => 'email']));
    }

    public function testEmailInvalid(): void
    {
        $validator = new FormValidator();
        $this->assertFalse($validator->validate(['email' => 'not-an-email'], ['email' => 'email']));
    }

    public function testMinLength(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['name' => 'hello'], ['name' => 'min_length:3']));
        $this->assertFalse($validator->validate(['name' => 'hi'], ['name' => 'min_length:3']));
    }

    public function testMaxLength(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['name' => 'hi'], ['name' => 'max_length:5']));
        $this->assertFalse($validator->validate(['name' => 'hello world'], ['name' => 'max_length:5']));
    }

    public function testNumeric(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['age' => '25'], ['age' => 'numeric']));
        $this->assertTrue($validator->validate(['age' => 42], ['age' => 'numeric']));
        $this->assertFalse($validator->validate(['age' => 'abc'], ['age' => 'numeric']));
    }

    public function testMin(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['age' => 18], ['age' => 'min:18']));
        $this->assertFalse($validator->validate(['age' => 17], ['age' => 'min:18']));
    }

    public function testMax(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['age' => 100], ['age' => 'max:150']));
        $this->assertFalse($validator->validate(['age' => 200], ['age' => 'max:150']));
    }

    public function testMatch(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(
            ['password' => 'secret', 'confirm' => 'secret'],
            ['confirm' => 'match:password']
        ));
        $this->assertFalse($validator->validate(
            ['password' => 'secret', 'confirm' => 'different'],
            ['confirm' => 'match:password']
        ));
    }

    public function testUrl(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['url' => 'https://example.com'], ['url' => 'url']));
        $this->assertFalse($validator->validate(['url' => 'not a url'], ['url' => 'url']));
    }

    public function testAlpha(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['name' => 'hello'], ['name' => 'alpha']));
        $this->assertFalse($validator->validate(['name' => 'hello123'], ['name' => 'alpha']));
    }

    public function testAlphaNum(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['code' => 'abc123'], ['code' => 'alpha_num']));
        $this->assertFalse($validator->validate(['code' => 'abc 123'], ['code' => 'alpha_num']));
    }

    public function testDate(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['date' => '2024-01-15'], ['date' => 'date']));
        $this->assertFalse($validator->validate(['date' => 'not-a-date'], ['date' => 'date']));
    }

    public function testIn(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['role' => 'admin'], ['role' => 'in:admin,moderator,user']));
        $this->assertFalse($validator->validate(['role' => 'superadmin'], ['role' => 'in:admin,moderator,user']));
    }

    public function testMultipleRules(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(
            ['email' => 'user@example.com'],
            ['email' => 'required|email']
        ));
        $this->assertFalse($validator->validate(
            ['email' => ''],
            ['email' => 'required|email']
        ));
    }

    public function testMultipleFields(): void
    {
        $validator = new FormValidator();
        $result = $validator->validate(
            ['name' => '', 'email' => 'not-email'],
            ['name' => 'required', 'email' => 'required|email']
        );
        $this->assertFalse($result);
        $this->assertCount(1, $validator->getErrorsForField('name'));
        $this->assertCount(1, $validator->getErrorsForField('email'));
    }

    public function testCustomRule(): void
    {
        $validator = new FormValidator();
        $validator->addRule('is_positive', function ($value) {
            return is_numeric($value) && $value > 0;
        });

        $this->assertTrue($validator->validate(['amount' => 10], ['amount' => 'is_positive']));
        $this->assertFalse($validator->validate(['amount' => -5], ['amount' => 'is_positive']));
    }

    public function testRemoveRule(): void
    {
        $validator = new FormValidator();
        $validator->addRule('custom', fn() => false);
        $validator->removeRule('custom');

        // Should be treated as unknown rule (ignored)
        $this->assertTrue($validator->validate(['field' => 'value'], ['field' => 'custom']));
    }

    public function testErrors(): void
    {
        $validator = new FormValidator();
        $validator->validate(['name' => '', 'email' => 'bad'], ['name' => 'required', 'email' => 'email']);

        $this->assertTrue($validator->hasErrors());
        $this->assertTrue($validator->hasError('name'));
        $this->assertNotNull($validator->getFirstError('name'));
        $this->assertNotNull($validator->getFirstErrorMessage());
        $this->assertCount(2, $validator->getErrors());
    }

    public function testNoErrors(): void
    {
        $validator = new FormValidator();
        $validator->validate(['name' => 'John'], ['name' => 'required']);

        $this->assertFalse($validator->hasErrors());
        $this->assertFalse($validator->hasError('name'));
        $this->assertNull($validator->getFirstError('name'));
    }

    public function testSetFieldLabels(): void
    {
        $validator = new FormValidator();
        $validator->setFieldLabels(['email' => 'Email Address']);

        $validator->validate(['email' => ''], ['email' => 'required']);
        $errors = $validator->getErrorsForField('email');
        $this->assertStringContainsString('Email Address', $errors[0]);
    }

    public function testStaticCheck(): void
    {
        $result = FormValidator::check(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($result);

        $result = FormValidator::check(['name' => ''], ['name' => 'required']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testEmptyOptionalFieldSkipsValidation(): void
    {
        $validator = new FormValidator();
        // Empty field without 'required' should pass all other rules
        $this->assertTrue($validator->validate(['name' => ''], ['name' => 'email|min_length:5']));
    }

    public function testAlphaCyrillic(): void
    {
        $validator = new FormValidator();
        $this->assertTrue($validator->validate(['name' => 'Привет'], ['name' => 'alpha']));
        $this->assertFalse($validator->validate(['name' => 'Привет123'], ['name' => 'alpha']));
    }
}
