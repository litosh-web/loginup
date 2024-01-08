<?php
$scriptProperties = ($scriptProperties) ? $scriptProperties : [];
$scriptProperties['processorsPath'] = MODX_CORE_PATH . 'components/loginup/processors/web/';

$lup = $modx->getService('luplogin', 'luplogin', MODX_CORE_PATH . 'components/loginup/model/');
$luplogin = new luplogin($modx, $scriptProperties);

$controller = $luplogin->loadController('Register');
return $controller->run($scriptProperties);