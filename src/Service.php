<?php

declare(strict_types=1);

namespace App;

use App\Controller\Controller;

class Service
{
    public function __construct(private Controller $controller)
    {
    }
}
