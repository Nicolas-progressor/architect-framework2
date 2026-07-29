<?php

declare(strict_types=1);

namespace app\modules\testmodule\model;

class testmodule
{
    public function getData(): array
    {
        return [
            'message' => 'Data from testmodule model',
            'timestamp' => time(),
        ];
    }
}
