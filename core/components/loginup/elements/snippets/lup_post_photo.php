<?php

//get user
if ($profile = $hook->getValue('register.profile')) {
} else if ($profile = $hook->getValue('updateprofile.profile')) {
} else {
    return;
}

require_once MODX_CORE_PATH . 'model/phpthumb/phpthumb.class.php';
$lup = $modx->getService('lup', 'lup', MODX_CORE_PATH . 'components/loginup/model/');
$lup = new lup($modx, $scriptProperties);

if ($photo = $hook->getValue('photo')) {

//get info of photo
    $namePhoto = $photo['name'];
    $extFile = mb_strtolower(pathinfo($namePhoto, PATHINFO_EXTENSION));

//allowed photo type
    $accesstype = $modx->getOption('lup_accesstype');
    $accesstype = explode(',', $accesstype);
    $accesstype = array_map('trim', $accesstype);
    $accesstype = array_map('strtolower', $accesstype);

    if (in_array($extFile, $accesstype)) { //if allowed - work
//id for name of photo and path
        $id = $profile->get('id');
        $uploaddir = $lup->getUploadFolder();

//rename photo
        $nameFilePhoto = 'user_' . $id . '.' . $extFile;
        $uploadfile = $uploaddir . $nameFilePhoto;
        $uploaddir_base = MODX_BASE_PATH . $uploaddir;
        $uploadfile_move = $uploaddir_base . $nameFilePhoto;

        //check path
        if (!is_dir($uploaddir_base)) {
            mkdir($uploaddir_base, 755, true);
        }

        if (move_uploaded_file($photo['tmp_name'], $uploadfile_move)) {
            $phpThumb = new phpThumb();
            $phpThumb->setSourceFilename($uploadfile_move);

            //set parametres
            $phpThumb->setParameter('w', $modx->getOption('lup_width'));
            $phpThumb->setParameter('h', $modx->getOption('lup_height'));
            $phpThumb->setParameter('zc', $modx->getOption('lup_zc'));
            $phpThumb->setParameter('q', $modx->getOption('lup_quality'));

            //generate thumbnail
            if ($phpThumb->GenerateThumbnail()) {
                if ($phpThumb->RenderToFile($uploadfile_move)) {
                    $profile->get('photo');
                    $profile->toArray();
                    $profile->set('photo', $uploadfile);
                    $profile->save();
                } else {
                    $modx->log(modX::LOG_LEVEL_ERROR, 'Error while saving this image ' . $uploadfile_move);
                }
            } else {
                $modx->log(modX::LOG_LEVEL_ERROR, print_r($phpThumb->debugmessages, 1));
            }
        }
    } else { //set null field
        $profile->get('photo');
        $profile->toArray();
        $profile->set('photo', '');
        $profile->save();
    }
}

return true;