<?php

declare(strict_types=1);

namespace Tests\Services\Form;

use Architect\Services\Form\FormBuilder;
use PHPUnit\Framework\TestCase;

class FormBuilderTest extends TestCase
{
    public function testOpenForm(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->open('/submit');
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('action="/submit"', $html);
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString('csrf_token', $html);
    }

    public function testOpenGetForm(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->open('/search', 'GET');
        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringNotContainsString('csrf_token', $html);
    }

    public function testCloseForm(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->close();
        $this->assertSame('</form>', $html);
    }

    public function testTextField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->textField('name', 'John');
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('value="John"', $html);
    }

    public function testEmailField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->emailField('email', 'test@example.com');
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    public function testPasswordField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->passwordField('password');
        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('value=""', $html);
    }

    public function testHiddenField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->hidden('token', 'abc123');
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="abc123"', $html);
    }

    public function testNumberField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->numberField('age', 25);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('value="25"', $html);
    }

    public function testTextarea(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->textarea('bio', 'Hello World');
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="bio"', $html);
        $this->assertStringContainsString('Hello World', $html);
        $this->assertStringContainsString('</textarea>', $html);
    }

    public function testSelect(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->select('role', ['admin' => 'Admin', 'user' => 'User'], 'admin');
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="role"', $html);
        $this->assertStringContainsString('<option', $html);
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Admin', $html);
        $this->assertStringContainsString('User', $html);
    }

    public function testSelectWithOptionsArray(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->select('color', [['red', 'Red'], ['blue', 'Blue']], 'blue');
        $this->assertStringContainsString('Red', $html);
        $this->assertStringContainsString('Blue', $html);
    }

    public function testCheckbox(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->checkbox('terms', '1', true, 'I agree');
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('I agree', $html);
    }

    public function testRadio(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->radio('gender', 'male', true, 'Male');
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringContainsString('Male', $html);
    }

    public function testSubmitButton(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->submitButton('Send');
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('Send', $html);
    }

    public function testResetButton(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->resetButton('Reset');
        $this->assertStringContainsString('type="reset"', $html);
        $this->assertStringContainsString('Reset', $html);
    }

    public function testButton(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->button('Click me');
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('Click me', $html);
    }

    public function testButtonAsLink(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->button('Go', '/home');
        $this->assertStringContainsString('<a href="/home"', $html);
        $this->assertStringContainsString('Go', $html);
    }

    public function testFileField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->fileField('avatar');
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('name="avatar"', $html);
    }

    public function testDateField(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->dateField('birthday', '2024-01-15');
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('value="2024-01-15"', $html);
    }

    public function testLoginAlias(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->login();
        $this->assertStringContainsString('action="/login"', $html);
        $this->assertStringContainsString('name="login_form"', $html);
    }

    public function testRegisterAlias(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->register();
        $this->assertStringContainsString('action="/register"', $html);
        $this->assertStringContainsString('name="register_form"', $html);
    }

    public function testSetData(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $builder->setData(['name' => 'John', 'email' => 'john@example.com']);
        $html = $builder->textField('name');
        $this->assertStringContainsString('value="John"', $html);
    }

    public function testRenderErrors(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $builder->setErrors(['name' => ['Name is required']]);
        $html = $builder->renderError('name');
        $this->assertStringContainsString('invalid-feedback', $html);
        $this->assertStringContainsString('Name is required', $html);
    }

    public function testRenderAllErrors(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $builder->setErrors([
            'name' => ['Name is required'],
            'email' => ['Email is invalid'],
        ]);
        $html = $builder->renderAllErrors();
        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('Name is required', $html);
        $this->assertStringContainsString('Email is invalid', $html);
    }

    public function testRenderNoErrors(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $this->assertSame('', $builder->renderError('name'));
        $this->assertSame('', $builder->renderAllErrors());
    }

    public function testStaticCreate(): void
    {
        $builder = FormBuilder::create();
        $this->assertInstanceOf(FormBuilder::class, $builder);
    }

    public function testEscape(): void
    {
        $builder = new FormBuilder(new \Architect\Services\Form\CSRFTokenManager(new ArraySession()));
        $html = $builder->open('/test?x=1&y=2');
        $this->assertStringContainsString('&amp;', $html);
    }
}
