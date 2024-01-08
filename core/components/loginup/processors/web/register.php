<?php

require_once MODX_CORE_PATH . 'components/login/processors/register.php';

/**
 * Login
 *
 * Copyright 2010 by Jason Coward <jason@modxcms.com> and Shaun McCormick
 * <shaun@modxcms.com>
 *
 * Login is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * Login is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Login; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package login
 */
/**
 * Handle register form
 *
 * @package login
 * @subpackage processors
 */
class LupLoginRegisterProcessor extends LoginRegisterProcessor {

    public function setCachePassword($password) {
        /* now set new password to registry to prevent middleman attacks.
         * Will read from the registry on the confirmation page. */
        $this->modx->getService('registry', 'registry.modRegistry');
        $this->modx->registry->addRegister('login','registry.modFileRegister');
        $this->modx->registry->login->connect();
        $this->modx->registry->login->subscribe('/useractivation/');
        //$this->modx->log(1, 'u' . $this->user->get('id'));
        $this->modx->registry->login->send('/useractivation/',array('u' . $this->user->get('id') => $password),array(
            'ttl' => ($this->controller->getProperty('activationttl',180)*60),
        ));
        /* set cachepwd here to prevent re-registration of inactive users */
        $this->user->set('cachepwd',md5($password));
        if ($this->live) {
            $success = $this->user->save();
        } else {
            $success = true;
        }
        if (!$success) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'[Login] Could not update cachepwd for activation for User: '.$this->user->get('username'));
        }
        return $success;
    }

    public function gatherActivationEmailProperties() {
        /* generate a password and encode it and the username into the url */
        $pword = $this->modx->user->generatePassword();
        $confirmParams['lp'] = $this->login->base64url_encode($pword);
        $confirmParams['lu'] = $this->login->base64url_encode('u' . $this->user->get('id'));
        $confirmParams = array_merge($this->persistParams,$confirmParams);

        /* if using redirectBack param, set here to allow dynamic redirection
         * handling from other forms.
         */
        $redirectBack = $this->modx->getOption('redirectBack',$_REQUEST,$this->controller->getProperty('redirectBack',''));
        if (!empty($redirectBack)) {
            $confirmParams['redirectBack'] = $redirectBack;
        }
        $redirectBackParams = $this->modx->getOption('redirectBackParams',$_REQUEST,$this->controller->getProperty('redirectBackParams',''));
        if (!empty($redirectBackParams)) {
            $confirmParams['redirectBackParams'] = $redirectBackParams;
        }

        /* generate confirmation url */
        if ($this->login->inTestMode) {
            $confirmUrl = $this->modx->makeUrl(1,'',$confirmParams,'full');
        } else {
            $confirmUrl = $this->modx->makeUrl($this->controller->getProperty('activationResourceId',1),'',$confirmParams,'full');
        }

        /* set confirmation email properties */
        $emailTpl = $this->controller->getProperty('activationEmailTpl','lgnActivateEmailTpl');
        $emailTplAlt = $this->controller->getProperty('activationEmailTplAlt','');
        $emailTplType = $this->controller->getProperty('activationEmailTplType','modChunk');
        $emailProperties = $this->user->toArray();
        $emailProperties['confirmUrl'] = $confirmUrl;
        $emailProperties['tpl'] = $emailTpl;
        $emailProperties['tplAlt'] = $emailTplAlt;
        $emailProperties['tplType'] = $emailTplType;
        $emailProperties['password'] = $this->dictionary->get($this->controller->getProperty('passwordField','password'));

        $this->setCachePassword($pword);
        return $emailProperties;
    }

}
return 'LupLoginRegisterProcessor';
