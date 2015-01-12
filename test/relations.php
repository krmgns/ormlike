<?php
header('Content-Type: text/plain');

include('inc.php');

/******************************************/

class Users extends ORMLike {
    protected $_table = 'users';
    protected $_primaryKey = 'id';

    protected $_relations = array(
        'select' => array('leftJoin' => array(
            array('table' => 'users_log', 'foreignKey' => 'user_id', 'field' => 'last_login_time', 'fieldPrefix' => '', 'groupBy' => 'users_log.user_id'),
            array('table' => 'users_point', 'foreignKey' => 'user_id', 'field' => 'Sum(point)', 'fieldPrefix' => '', 'groupBy' => 'users_point.user_id'),
        )),
        'delete' => array('cascade' => array(
            array('table' => 'users_log', 'foreignKey' => 'user_id'),
            array('table' => 'users_point', 'foreignKey' => 'user_id'),
        )),
        // @todo if needs
        // 'update' => array('cascade' => array(
        //     array('table' => 'users_point', 'foreignKey' => 'user_id')
        // )),
        // @todo if needs
        // 'insert' => array('cascade' => array('table' => ...)),
    );
}

$users = new Users();

// $users->remove(4);
// $users->remove([4,5,6]);

pre('...');
// pre($users->findAll()->toArray());

$user = $users->find(1);
pre($user->point);
