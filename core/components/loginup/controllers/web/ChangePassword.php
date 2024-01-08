<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/ChangePassword.php';

/**
 * Handles changing of user's password via a form
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginChangePasswordController extends LoginChangePasswordController
{
    public $namespace = 'loginup';
    public $type = 'change';

    public function initialize()
    {
        $this->modx->lexicon->load('login:register');
        $this->modx->lexicon->load('login:changepassword');
        $this->setDefaultProperties(array(
            'fieldConfirmNewPassword' => 'password_new_confirm',
            'fieldNewPassword' => 'password_new',
            'fieldOldPassword' => 'password_old',
            'placeholderPrefix' => 'logcp.',
            'preHooks' => '',
            'redirectToLogin' => true,
            'reloadOnSuccess' => false,
            'reloadOnSuccessVar' => 'logcp-success',
            'submitVar' => 'logcp-submit',
            'successMessage' => $this->modx->lexicon('login.password_changed'),
            'validate' => '',
            'validateOldPassword' => true,
            'errTpl' => 'lgnErrTpl',
            'jsonResponse' => false
        ));
    }

    /**
     * Handle the form submission, properly sanitizing and validating the data, then processing the password change
     * @return string
     */
    public function handlePost()
    {
        $this->loadDictionary();
        $this->removeSubmitVar();

        $this->errors = $this->validate();
        if (empty($this->errors)) {
            if ($this->loadPreHooks()) {
                $this->validateOldPassword();
                $this->validatePasswordLength();
                $this->confirmMatchedPasswords();
                if (empty($this->errors)) {
                    $this->changePassword();
                } else {
                    $errorMsg = $this->prepareFailureMessage();
                    $placeholderPrefix = rtrim($this->getProperty('placeholderPrefix', 'logcp.'), '.');
                    $this->modx->toPlaceholder('error_message', $errorMsg, $placeholderPrefix);
                }
            }
        } else {
            $errorMsg = $this->prepareFailureMessage();
            $errors = $this->validator->getErrors();

            // Return JSON error response if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => $errorMsg,
                    'data' => $errors
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
        }

        $this->setToPlaceholders();
        return '';
    }

    /**
     * Actually attempt to change the password
     *
     * @return boolean
     */
    public function changePassword()
    {
        $fieldNewPassword = $this->getProperty('fieldNewPassword');
        $fieldOldPassword = $this->getProperty('fieldOldPassword');
        $validateOldPassword = $this->getProperty('validateOldPassword', true, 'isset');
        $newPassword = $this->dictionary->get($fieldNewPassword);
        $oldPassword = $this->dictionary->get($fieldOldPassword);

        /* attempt to change the password */
        $success = $this->modx->user->changePassword($newPassword, $oldPassword, $validateOldPassword);
        if (!$success) {
            /* for some reason it failed (possibly a plugin) so send error message */
            $this->errors[$fieldNewPassword] = $this->modx->lexicon('login.password_err_change');
        } else {
            $this->loadPostHooks();

            if (!$this->reloadOnSuccess()) {
                // Return JSON success response if requested.
                if ($this->getProperty('jsonResponse')) {
                    $jsonSuccessOutput = array(
                        'type' => $this->type,
                        'namespace' => $this->namespace,
                        'success' => true,
                        'message' => $this->modx->lexicon('login.password_changed')
                    );
                    header('Content-Type: application/json;charset=utf-8');
                    exit($this->modx->toJSON($jsonSuccessOutput));
                }
                $this->setSuccessMessagePlaceholder();
            }
        }
        return $success;
    }

    /**
     * Load any pre-password-change preHooks that can stop the event propagation
     * @return boolean
     */
    public function loadPreHooks()
    {
        $passed = true;
        $this->loadHooks('preHooks');
        $preHooks = $this->getProperty('preHooks', '');
        if (!empty($preHooks)) {
            $this->preHooks->loadMultiple($preHooks, $this->dictionary->toArray(), array(
                'user' => &$this->modx->user,
                'submitVar' => $this->getProperty('submitVar'),
                'reloadOnSuccess' => $this->getProperty('reloadOnSuccess'),
                'fieldOldPassword' => $this->getProperty('fieldOldPassword'),
                'fieldNewPassword' => $this->getProperty('fieldNewPassword'),
                'fieldConfirmNewPassword' => $this->getProperty('fieldConfirmNewPassword'),
            ));
            $values = $this->preHooks->getValues();
            if (!empty($values)) {
                $this->dictionary->fromArray($values);
            }
        }
        /* process preHooks */
        if ($this->preHooks->hasErrors()) {

            $es = $this->preHooks->getErrors();
            $errorMsg = $this->preHooks->getErrorMessage();

            // Return JSON error response if requested.
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

            $placeholderPrefix = rtrim($this->getProperty('placeholderPrefix', 'logcp.'), '.');
            $errorPrefix = ($placeholderPrefix) ? $placeholderPrefix . '.error' : 'error';
            $this->modx->toPlaceholders($this->preHooks->getErrors(), $errorPrefix);
            $this->modx->toPlaceholder('error_message', $this->preHooks->getErrorMessage(), $placeholderPrefix);
            $passed = false;
        }
        return $passed;
    }

    /**
     * @param string $defaultErrorMessage
     * @return string
     */
    public function prepareFailureMessage($defaultErrorMessage = '')
    {
        $errorOutput = '';
        $errTpl = $this->getProperty('errTpl');
        $errors = $this->errors;

        // Return JSON error response if requested.
        if ($this->getProperty('jsonResponse')) {
            $jsonErrorOutput = array(
                'type' => $this->type,
                'namespace' => $this->namespace,
                'success' => false,
                'status' => 0,
                'message' => reset($errors),
                'data' => $errors
            );
            header('Content-Type: application/json;charset=utf-8');
            exit($this->modx->toJSON($jsonErrorOutput));
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $errorOutput .= $this->modx->getChunk($errTpl, array('msg' => $error));
            }
        } else {
            $errorOutput = $this->modx->getChunk($errTpl, array('msg' => $defaultErrorMessage));
        }
        return $errorOutput;
    }
}

return 'LupLoginChangePasswordController';
