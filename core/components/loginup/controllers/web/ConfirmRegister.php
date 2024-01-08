<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/ConfirmRegister.php';

/**
 * Confirms a User's Registration after activation
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginConfirmRegisterController extends LoginConfirmRegisterController {

    public $id;

    /**
     * Verify that the username/password hashes were correctly sent (base64 encoded in URL) to prevent middle-man attacks.
     *
     * @access public
     * @return boolean
     */
    public function verifyManifest() {
        $verified = false;
        if (empty($_REQUEST['lp']) || empty($_REQUEST['lu'])) {
            $this->redirectAfterFailure();
        } else {
            // get username and password from query params
            $id = $this->login->base64url_decode($_REQUEST['lu']);
            $this->id = preg_replace('/^u/i', '', $id);
            $this->password = $this->login->base64url_decode($_REQUEST['lp']);
            $verified = true;
        }
        return $verified;
    }

    /**
     * Validate we have correct user
     * @return modUser
     */
    public function getUser() {
        $this->user = $this->modx->getObject('modUser',array('id' => $this->id));
        if ($this->user == null) {
            $this->redirectAfterFailure();
        } elseif ($this->user->get('active')) {
            $activePage = $this->getProperty('activePage', false, 'isset');
            $this->redirectAfterFailure($activePage);
        }
        return $this->user;
    }

    /**
     * Validate password to prevent middleman attacks
     * @return boolean
     */
    public function validatePassword() {
        $this->modx->getService('registry', 'registry.modRegistry');
        $this->modx->registry->addRegister('login','registry.modFileRegister');
        $this->modx->registry->login->connect();
        $this->modx->registry->login->subscribe('/useractivation/u'.$this->user->get('id'));
        $msgs = $this->modx->registry->login->read();
        if (empty($msgs)) $this->modx->sendErrorPage();
        $found = false;
        foreach ($msgs as $msg) {
            if ($msg == $this->password) {
                $found = true;
            }
        }
        if (!$found) {
            $this->redirectAfterFailure();
        }
        return $found;
    }
}
return 'LupLoginConfirmRegisterController';
