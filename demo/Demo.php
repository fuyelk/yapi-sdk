<?php

namespace fuyelk\yapi;

require __DIR__ . "/../src/Yapi.php";

$email = 'admin@admin.com';
$password = '123456';
$host = 'http://api.example.com:3000';

$yapi = new Yapi($email, $password, $host);
$res = $yapi->export(204, 'html', __DIR__ . '/temp/export.html');
if (!$res) {
    echo $yapi->getError();
    exit();
}

$res = $yapi->export(204, 'json', __DIR__ . '/temp/export.json');
if (!$res) {
    echo $yapi->getError();
    exit();
}

$res = $yapi->export(204, 'markdown', __DIR__ . '/temp/export.markdown');
if (!$res) {
    echo $yapi->getError();
    exit();
}

$res = $yapi->export(204, 'swagger', __DIR__ . '/temp/export.swagger');
if (!$res) {
    echo $yapi->getError();
    exit();
}

echo "success";
