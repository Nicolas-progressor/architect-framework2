<?php

declare(strict_types=1);

namespace Tests\Services\Form;

use Architect\Services\Form\CSRFTokenManager;
use PHPUnit\Framework\TestCase;

class CSRFTokenManagerTest extends TestCase
{
    private ArraySession $session;
    private CSRFTokenManager $manager;

    protected function setUp(): void
    {
        $this->session = new ArraySession();
        $this->manager = new CSRFTokenManager($this->session);
    }

    public function testGenerateToken(): void
    {
        $token = $this->manager->generateToken('login_form');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(0, strlen($token));
    }

    public function testGenerateTokenReturnsSameForSameForm(): void
    {
        $token1 = $this->manager->generateToken('login_form');
        $token2 = $this->manager->generateToken('login_form');
        $this->assertSame($token1, $token2);
    }

    public function testGenerateTokenDifferentForDifferentForms(): void
    {
        $token1 = $this->manager->generateToken('login_form');
        $token2 = $this->manager->generateToken('register_form');
        $this->assertNotSame($token1, $token2);
    }

    public function testValidateToken(): void
    {
        $token = $this->manager->generateToken('login_form');
        $this->assertTrue($this->manager->validateToken('login_form', $token));
    }

    public function testValidateTokenInvalid(): void
    {
        $this->manager->generateToken('login_form');
        $this->assertFalse($this->manager->validateToken('login_form', 'wrong-token'));
    }

    public function testValidateTokenEmpty(): void
    {
        $this->assertFalse($this->manager->validateToken('login_form', ''));
    }

    public function testValidateTokenUnknownForm(): void
    {
        $this->assertFalse($this->manager->validateToken('unknown', 'some-token'));
    }

    public function testRemoveToken(): void
    {
        $this->manager->generateToken('login_form');
        $this->manager->removeToken('login_form');
        $this->assertFalse($this->manager->validateToken('login_form', 'any-token'));
    }

    public function testGetTokenField(): void
    {
        $html = $this->manager->getTokenField('login_form');
        $this->assertStringContainsString('<input type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString('value="', $html);
    }

    public function testGetMetaTag(): void
    {
        $html = $this->manager->getMetaTag('login_form');
        $this->assertStringContainsString('<meta name="csrf-token"', $html);
        $this->assertStringContainsString('content="', $html);
    }

    public function testGetMetaTagDefaultForm(): void
    {
        $html = $this->manager->getMetaTag();
        $this->assertStringContainsString('<meta', $html);
    }

    public function testCleanExpiredTokens(): void
    {
        $this->manager->generateToken('login_form');
        $removed = $this->manager->cleanExpiredTokens();
        $this->assertIsInt($removed);
    }

    public function testTokensStoredInSession(): void
    {
        $this->manager->generateToken('login_form');
        $tokens = $this->session->get('csrf_tokens', []);
        $this->assertArrayHasKey('login_form', $tokens);
        $this->assertArrayHasKey('token', $tokens['login_form']);
        $this->assertArrayHasKey('expires_at', $tokens['login_form']);
    }

    public function testTokenFieldContainsToken(): void
    {
        $token = $this->manager->generateToken('my_form');
        $field = $this->manager->getTokenField('my_form');
        $this->assertStringContainsString($token, $field);
    }
}
