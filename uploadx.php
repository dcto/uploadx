<?php

require ('./classes/uploadx.class.php');

$uploadx = new uploadx;

$uploadx->save = './temp';
$uploadx->name = 'auto';
$uploadx->mini = '200,200,mini';
$uploadx->mark = './images/logo.png,0,60';
print_r($uploadx->mini('./temp/2.jpg'));

