<?php

$lup = $modx->getService('lup', 'lup', MODX_CORE_PATH . 'components/loginup/model/');
$lup = new lup($modx, $scriptProperties);

$lup->loadLexicon();
$lup->loadJsCssWeb();