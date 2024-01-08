<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/Register.php';

/**
 * Handles registration of users
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginRegisterController extends LoginRegisterController
{

    public $namespace = 'loginup';
    public $type = 'register';

    /**
     * Load default properties for this controller
     * @return void
     */
    public function initialize()
    {
        $this->modx->lexicon->load('login:register');
        $this->setDefaultProperties(array(
            'activation' => true,
            'activationEmail' => '',
            'activationEmailSubject' => $this->modx->lexicon('register.activation_email_subject'),
            'activationEmailTpl' => 'lgnActivateEmailTpl',
            'activationEmailTplType' => 'modChunk',
            'activationEmailTplAlt' => '',
            'activationResourceId' => '',
            'emailField' => 'email',
            'errTpl' => '<span class="error">[[+error]]</span>',
            'excludeExtended' => '',
            'moderatedResourceId' => '',
            'passwordField' => 'password',
            'persistParams' => '',
            'placeholderPrefix' => '',
            'preHooks' => '',
            'postHooks' => '',
            'redirectBack' => '',
            'redirectBackParams' => '',
            'redirectUnsetDefaultParams' => false,
            'submittedResourceId' => '',
            'submitVar' => 'login-register-btn',
            'successMsg' => '',
            'useExtended' => true,
            'usergroups' => '',
            'usernameField' => 'username',
            'validate' => '',
            'validatePassword' => true,
            'autoLogin' => false,
            'jsonResponse' => false,
            'validationErrorMessage' => $this->modx->lexicon('register.validation_error_message'),
            'preserveFieldsAfterRegister' => true,
        ));
    }

    /**
     * Handle the Register snippet business logic
     * @return string
     */
    public function process()
    {
        $this->checkForPost();
        $this->preLoad();

        if (!$this->hasPosted) {
            return '';
        }

        if (!$this->loadDictionary()) {
            return '';
        }
        $fields = $this->validateFields();
        $this->dictionary->reset();
        $this->dictionary->fromArray($fields);

        $this->validateUsername();
        if ($this->getProperty('validatePassword', true, 'isset')) {
            $this->validatePassword();
        }
        if ($this->getProperty('ensurePasswordStrength', false, 'isset')) {
            $this->ensurePasswordStrength();
        }
        if ($this->getProperty('generatePassword', false, 'isset')) {
            $this->generatePassword();
        }
        $this->validateEmail();

        $placeholderPrefix = rtrim($this->getProperty('placeholderPrefix', ''), '.');
        $errorPrefix = ($placeholderPrefix) ? $placeholderPrefix . '.error' : 'error';
        if ($this->validator->hasErrors()) {
            $errors = $this->validator->getErrors();
            // Return JSON error response if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => $this->getProperty('validationErrorMessage'),
                    'data' => $errors
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
            $this->modx->toPlaceholders($errors, $errorPrefix);
            $this->modx->toPlaceholder('validation_error', true, $placeholderPrefix);
            $this->modx->toPlaceholder('validation_error_message', $this->getProperty('validationErrorMessage'), $placeholderPrefix);
        } else {

            $this->loadPreHooks();

            /* process hooks */
            if ($this->preHooks->hasErrors()) {
                $this->modx->toPlaceholders($this->preHooks->getErrors(), $errorPrefix);
                $errorMsg = $this->preHooks->getErrorMessage();
                $this->modx->toPlaceholder('error.message', $errorMsg, $placeholderPrefix);

                // Return JSON error response if requested.
                if ($this->getProperty('jsonResponse')) {
                    $es = $this->preHooks->getErrors();
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

            } else {
                /* everything good, go ahead and register */
                //$this->modx->log(1, print_r($this->config['processorsPath'], 1));
                $result = $this->runProcessor('register');
                if ($result !== true) {
                    $this->modx->toPlaceholder('error.message', $result, $placeholderPrefix);
                } else {
                    // Return JSON success response if requested
                    if ($this->getProperty('jsonResponse')) {
                        $jsonSuccessOutput = array(
                            'type' => $this->type,
                            'namespace' => $this->namespace,
                            'success' => true,
                            'status' => 1,
                            'message' => $this->getProperty('successMsg', 'User registration successful.')
                        );
                        header('Content-Type: application/json;charset=utf-8');
                        exit($this->modx->toJSON($jsonSuccessOutput));
                    }
                    $this->modx->toPlaceholder('successMsg', $this->getProperty('successMsg', 'User registration successful.'), $placeholderPrefix);
                    $this->success = true;
                }
            }
        }

        $placeholders = $this->dictionary->toArray();

        $placeholders = $this->escapePlaceholders($placeholders);

        $this->modx->toPlaceholders($placeholders, $placeholderPrefix);
        foreach ($placeholders as $k => $v) {
            if (is_array($v)) {
                $this->modx->toPlaceholder($k, json_encode($v), $placeholderPrefix);
            }
        }

        if (!$this->success || $this->getProperty('preserveFieldsAfterRegister')) {
            $this->modx->setPlaceholders($this->dictionary->toArray(), $placeholderPrefix);
        }
        return '';
    }

}

return 'LupLoginRegisterController';
