<?php

declare(strict_types=1);

namespace app\modules\api\controller;

use pattern\controller;

/**
 * API контроллер (общий модуль - без приложения)
 *
 * По умолчанию работает БЕЗ шаблона
 *
 * @OA\Info(
 *     title="Architect Framework API",
 *     version="2.0.0",
 *     description="REST API для архитектурного фреймворка Architect RED 2"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Основной сервер"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Иван"),
 *     @OA\Property(property="email", type="string", format="email", example="ivan@example.com")
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="ok"),
 *     @OA\Property(property="message", type="string", example="API работает")
 * )
 */
class api extends controller
{
    /**
     * Корневой эндпоинт API.
     *
     * Возвращает статус и список доступных эндпоинтов.
     *
     * @OA\Get(
     *     path="/api",
     *     summary="Статус API",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="endpoints", type="object")
     *         )
     *     )
     * )
     */
    public function index_app_output(): void
    {
        $this->noTemplate();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'message' => 'API работает',
            'endpoints' => [
                '/api/users' => 'Список пользователей',
                '/api/user/{id}' => 'Пользователь по ID',
            ],
        ]);
    }

    /**
     * Список пользователей.
     *
     * @OA\Get(
     *     path="/api/users",
     *     summary="Список пользователей",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Массив пользователей",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/User")
     *             )
     *         )
     *     )
     * )
     */
    public function users_app_output(): void
    {
        $this->noTemplate();

        header('Content-Type: application/json');
        echo json_encode([
            'users' => [
                ['id' => 1, 'name' => 'Иван'],
                ['id' => 2, 'name' => 'Пётр'],
                ['id' => 3, 'name' => 'Анна'],
            ],
        ]);
    }

    /**
     * Получить пользователя по ID.
     *
     * @OA\Get(
     *     path="/api/user/{id}",
     *     summary="Пользователь по ID",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID пользователя",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Данные пользователя",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     )
     * )
     */
    public function user_app_output(): void
    {
        $this->noTemplate();

        $id = $this->segment(3, '0');

        header('Content-Type: application/json');
        echo json_encode([
            'id' => $id,
            'name' => 'Пользователь ' . $id,
            'email' => 'user' . $id . '@example.com',
        ]);
    }
}
