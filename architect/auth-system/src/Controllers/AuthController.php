<?php

declare(strict_types=1);

namespace Architect\Auth\Controllers;

use Architect\Auth\Contracts\AuthenticationInterface;
use Architect\Auth\Contracts\AuthorizationInterface;
use Architect\Auth\Contracts\UserProviderInterface;
use Architect\Services\Mvc\Controller;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationInterface $auth,
        private AuthorizationInterface $authorization,
        private UserProviderInterface $userProvider
    ) {
        parent::__construct();
    }

    /**
     * Форма входа.
     */
    public function loginAction(): void
    {
        // Если уже авторизован - редирект
        if ($this->auth->isLoggedIn()) {
            $this->redirect($this->getRedirectUrl());
        }

        $this->render('login');
    }

    /**
     * Обработка формы входа.
     */
    public function authenticateAction(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->flash('error', 'Пожалуйста, заполните все поля');
            $this->redirect('/login');
            return;
        }

        if ($this->auth->login($username, $password)) {
            $this->flash('success', 'Вы успешно вошли в систему');
            $this->redirect($this->getRedirectUrl());
            return;
        }

        $this->flash('error', 'Неверное имя пользователя или пароль');
        $this->redirect('/login');
    }

    /**
     * Выход.
     */
    public function logoutAction(): void
    {
        $this->auth->logout();
        $this->flash('success', 'Вы вышли из системы');
        $this->redirect('/');
    }

    /**
     * Форма регистрации.
     */
    public function registerAction(): void
    {
        // Если уже авторизован - редирект
        if ($this->auth->isLoggedIn()) {
            $this->redirect('/');
        }

        $this->render('register');
    }

    /**
     * Обработка регистрации.
     */
    public function createAction(): void
    {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Валидация
        if (empty($username) || empty($email) || empty($password)) {
            $this->flash('error', 'Пожалуйста, заполните все поля');
            $this->redirect('/register');
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Пароли не совпадают');
            $this->redirect('/register');
            return;
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'Пароль должен быть не менее 6 символов');
            $this->redirect('/register');
            return;
        }

        // Проверка существования пользователя
        if ($this->userProvider->usernameExists($username)) {
            $this->flash('error', 'Пользователь с таким именем уже существует');
            $this->redirect('/register');
            return;
        }

        if ($this->userProvider->emailExists($email)) {
            $this->flash('error', 'Пользователь с таким email уже существует');
            $this->redirect('/register');
            return;
        }

        // Регистрация
        $user = $this->userProvider->create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => 'user', // роль по умолчанию
        ]);

        if (!$user) {
            $this->flash('error', 'Ошибка при создании пользователя');
            $this->redirect('/register');
            return;
        }

        // Автоматический вход после регистрации
        $this->auth->loginUser($user);

        $this->flash('success', 'Регистрация успешна! Добро пожаловать!');
        $this->redirect('/');
    }

    /**
     * Получить URL для редиректа после входа.
     */
    protected function getRedirectUrl(): string
    {
        return $_GET['redirect'] ?? '/';
    }

    /**
     * Установить flash сообщение.
     */
    protected function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash'][$type] = $message;
    }
}
