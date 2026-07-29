<?php

declare(strict_types=1);

namespace app\admin\modules\dashboard\model;

use Architect\Services\Mvc\ModelBase;

class dashboard extends ModelBase
{
    public function getStats(): array
    {
        $userCount = 0;
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            $userCount = method_exists($userService, 'count') ? $userService->count() : count($userService->getAll());
        }

        return [
            'users' => $userCount,
            'modules' => 2,
            'version' => '2.0.0',
        ];
    }
}
