<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/ForgotPassword.php';

/**
 * Handles the Forgot Password form for users
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginForgotPasswordController extends LoginForgotPasswordController
{
    public $namespace = 'loginup';
    public $type = 'forgot';

    public function initialize()
    {
        $this->modx->lexicon->load('login:forgotpassword');
        $this->setDefaultProperties(array(
            'tpl' => 'lgnForgotPassTpl',
            'tplType' => 'modChunk',
            'sentTpl' => 'lgnForgotPassSentTpl',
            'sentTplType' => 'modChunk',
            'emailTpl' => 'lgnForgotPassEmail',
            'emailTplAlt' => '',
            'emailTplType' => 'modChunk',
            'emailSubject' => '',
            'preHooks' => '',
            'resetResourceId' => 1,
            'redirectTo' => false,
            'redirectParams' => '',
            'submitVar' => 'login_fp_service',
            'emailFrom' => '',
            'emailFromName' => '',
            'emailSender' => '',
            'emailReplyTo' => '',
            'jsonResponse' => false
        ));
    }

    /**
     * Process the controller
     * @return string
     */
    public function process()
    {
        $this->templateToLoad = $this->getProperty('tpl');
        $this->templateTypeToLoad = $this->getProperty('tplType');

        /* get the request URI */
        $this->placeholders['loginfp.request_uri'] = empty($_POST['request_uri']) ? $this->login->getRequestURI() : $_POST['request_uri'];

        if ($this->hasPost()) {
            $this->handlePost();
            $fields = $this->dictionary->toArray();
            $fields = $this->escapePlaceholders($fields);
            foreach ($fields as $k => $v) {
                $this->placeholders['loginfp.post.' . $k] = $v;
            }
        }

        if ($this->getProperty('jsonResponse')) {
            $jsonOutput = [
                'type' => $this->type,
                'namespace' => $this->namespace,
                'success' => true,
                'message' => $this->modx->lexicon('login.user_err_nf_' . $this->usernameField),
            ];
            return $this->modx->toJSON($jsonOutput);
        } else {
            return $this->login->getChunk($this->templateToLoad, $this->placeholders, $this->templateTypeToLoad);
        }
    }


    /**
     * Run any preHooks to process before submitting the form
     * @return boolean
     */
    public function runPreHooks()
    {
        $success = true;
        $preHooks = $this->getProperty('preHooks', '');
        if (!empty($preHooks)) {
            $this->loadHooks('preHooks');
            $this->preHooks->loadMultiple($preHooks, $this->dictionary->toArray(), array(
                'mode' => Login::MODE_FORGOT_PASSWORD,
            ));
            /* process preHooks */
            if ($this->preHooks->hasErrors()) {
                $success = false;
                $this->modx->toPlaceholders($this->preHooks->getErrors(), $this->getProperty('errorPrefix'));

                $errorMsg = $this->preHooks->getErrorMessage();
                $errorOutput = $this->formatError($errorMsg);
                $this->modx->setPlaceholder('errors', $errorOutput);

                // Return JSON error response if requested.
                $es = $this->preHooks->getErrors();
                if ($this->getProperty('jsonResponse')) {
                    $jsonErrorOutput = array(
                        'type' => $this->type,
                        'namespace' => $this->namespace,
                        'success' => false,
                        'status' => 0,
                        'message' => $errorMsg,
                        'data' => $es
                    );
                    header('Content-Type: application/json;charset=utf-8');
                    exit($this->modx->toJSON($jsonErrorOutput));
                }

            }

            $values = $this->preHooks->getValues();
            if (!empty($values)) {
                $this->dictionary->fromArray($values);
            }
        }
        return $success;
    }

}

return 'LupLoginForgotPasswordController';
