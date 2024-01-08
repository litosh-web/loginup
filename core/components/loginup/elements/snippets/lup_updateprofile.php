<?php

$lup = $modx->getService('luplogin', 'luplogin', MODX_CORE_PATH . 'components/loginup/model/');
$lup = new luplogin($modx, $scriptProperties);

$controller = $lup->loadController('UpdateProfile');
return $controller->run($scriptProperties);