<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function __construct()
    {
        parent::__construct(
            "users",
            ["name"],
            ['id']
        );
    }
}
