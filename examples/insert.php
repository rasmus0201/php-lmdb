<?php

use Bundsgaard\Lmdb\Database;

$db = new Database('db.mdb', Database::WRITE_MODE);

$db->put('hest', 'horse');
var_dump($db->get('hest'));
