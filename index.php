<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "vendor/autoload.php";

use App\Models\User;

$user = new User();
$user->name = "Nome";
$user->last_name = "Sobrenome";
$user->email = "email@email.com";
//$user->save();

var_dump($user);
