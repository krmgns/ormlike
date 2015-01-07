<?php
header('Content-Type: text/plain');

include('inc.php');

/******************************************/

class Users extends ORMLike {
    protected $_table = 'users';
    protected $_primaryKey = 'id';
}


$users = new Users();

$user = $users->find(1);
pre($user->toArray());

pre('...');
pre($users);