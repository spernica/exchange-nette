<?php

include __DIR__ . '/../vendor/autoload.php';

define('TEMP_DIR', __DIR__ . '/temp/' . getmypid());

Tester\Environment::setup();

Tracy\Debugger::enable(FALSE);

