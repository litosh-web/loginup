<?php

require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';

class lup_photo_remove extends modProcessor
{

    public function process()
    {
        if (!$this->modx->user->isAuthenticated('web')) {
            return $this->failure();
        }

        $lup = $this->modx->getService('lup', 'lup', MODX_CORE_PATH . 'components/loginup/model/');

        $user = $this->modx->getUser();
        $profile = $user->getOne('Profile');
        $profile->set('photo', '');
        $profile->save();

        if ($lup->removeUserPhoto($user->id)) {
            return $this->success();
        }
    }

}

return 'lup_photo_remove';