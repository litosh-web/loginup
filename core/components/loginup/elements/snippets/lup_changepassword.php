<?php
$lup = $modx->getService('luplogin', 'luplogin', MODX_CORE_PATH . 'components/loginup/model/');
$luplogin = new luplogin($modx, $scriptProperties);

$controller = $luplogin->loadController('ChangePassword');
return $controller->run($scriptProperties);