<?php
$modx->lexicon->load('loginup:default');

if ($photo = $hook->getValue('photo')) {

//get info of photo
    $namePhoto = $photo['name'];
    $extFile = mb_strtolower(pathinfo($namePhoto, PATHINFO_EXTENSION));

//allowed photo type
    $accesstype = $modx->getOption('lup_accesstype');
    $accesstype = explode(',', $accesstype);
    $accesstype = array_map('trim', $accesstype);
    $accesstype = array_map('strtolower', $accesstype);

    if (!in_array($extFile, $accesstype)) {
        $hook->addError('photo', $modx->lexicon('lup_message_photo_wrong'));
        return false;
    }
}

return true;