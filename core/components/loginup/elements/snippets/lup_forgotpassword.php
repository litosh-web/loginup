<?php
$lup = $modx->getService('luplogin', 'luplogin', MODX_CORE_PATH . 'components/loginup/model/');
$luplogin = new luplogin($modx, $scriptProperties);

$controller = $luplogin->loadController('ForgotPassword');
return $controller->run($scriptProperties);