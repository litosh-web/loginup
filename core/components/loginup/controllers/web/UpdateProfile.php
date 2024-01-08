<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/UpdateProfile.php';

/**
 * Handles updating the profile of the active user
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginUpdateProfileController extends LoginUpdateProfileController
{
    public $namespace = 'loginup';
    public $type = 'update';

    /**
     * Load default properties for this controller
     * @return void
     */
    public function initialize()
    {
        $this->modx->lexicon->load('login:updateprofile');
        $this->modx->lexicon->load('login:register');
        $this->setDefaultProperties(array(
            'allowedExtendedFields' => '',
            'emailField' => 'email',
            'errTpl' => '<span class="error">[[+error]]</span>',
            'excludeExtended' => '',
            'placeholderPrefix' => '',
            'postHooks' => '',
            'preHooks' => '',
            'redirectToLogin' => true,
            'reloadOnSuccess' => false,
            'submitVar' => 'login-updprof-btn',
            'successKey' => 'updpsuccess',
            'successMsg' => $this->modx->lexicon('login.profile_updated'),
            'successMsgPlaceholder' => 'error.message',
            'syncUsername' => false,
            'useExtended' => true,
            'user' => '',
            'validate' => '',
            'errorDelimited' => '<br>',
            'jsonResponse' => false
        ));
    }

    /**
     * Handle the UpdateProfile snippet business logic
     * @return string
     */
    public function process()
    {
        if (!$this->verifyAuthentication()) return '';
        if (!$this->getUser()) return '';
        if (!$this->getProfile()) return '';

        $this->checkForSuccessMessage();
        $validate = true;
        if ($this->hasPost()) {
            $this->loadDictionary();
            if ($this->validate()) {
                if ($this->runPreHooks()) {
                    /* update the profile */
                    $result = $this->runProcessor('UpdateProfile');
                    if ($result !== true) {
                        $this->modx->toPlaceholder('message', $result, 'error');
                    } else if ($this->getProperty('reloadOnSuccess', true, 'isset')) {
                        $url = $this->modx->makeUrl($this->modx->resource->get('id'), '', array(
                            $this->getProperty('successKey', 'updpsuccess') => 1,
                        ), 'full');
                        $this->modx->sendRedirect($url);
                    } else {
                        $this->modx->setPlaceholder('login.update_success', true);
                    }
                } else {
                    $validate = false;
                }
            } else {
                $validate = false;
            }
        }

        $this->setFieldPlaceholders();

        if ($validate === false) {
            $placeholderPrefix = rtrim($this->getProperty('placeholderPrefix'), '.');

            $fields = $this->dictionary->toArray();
            $fields = $this->escapePlaceholders($fields);

            $this->modx->toPlaceholders($fields, $placeholderPrefix);
        }

        if ($this->getProperty('jsonResponse')) {
            $jsonErrorOutput = array(
                'type' => $this->type,
                'namespace' => $this->namespace,
                'success' => true,
                'status' => 1,
                'message' => $this->modx->lexicon('login.profile_updated'),
            );
            return $this->modx->toJSON($jsonErrorOutput);
        } else {
            return '';
        }
    }


    /**
     * Validate the form submission
     *
     * @return boolean
     */
    public function validate()
    {
        $validated = false;
        $this->loadValidator();
        $fields = $this->validator->validateFields($this->dictionary, $this->getProperty('validate', ''));
        foreach ($fields as $k => $v) {
            $fields[$k] = str_replace(array('[', ']'), array('&#91;', '&#93;'), $v);
        }
        $this->dictionary->fromArray($fields);

        $this->removeSubmitVar();
        $this->preventDuplicateEmails();

        if ($this->validator->hasErrors()) {
            $placeholders = $this->dictionary->toArray();
            $placeholderPrefix = rtrim($this->getProperty('placeholderPrefix'), '.');
            $errorPrefix = ($placeholderPrefix) ? $placeholderPrefix . '.error' : 'error';
            $this->modx->toPlaceholders($this->validator->getErrors(), $errorPrefix);
            $this->modx->toPlaceholders($placeholders, $placeholderPrefix);
            foreach ($placeholders as $k => $v) {
                if (is_array($v)) {
                    $this->modx->toPlaceholder($k, json_encode($v), $placeholderPrefix);
                }
            }
            $errors = array();
            $es = $this->validator->getErrors();

            // Return JSON error response if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => reset($es),
                    'data' => $es
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }

            foreach ($es as $key => $error) {
                $errors['message'] .= $error . $this->getProperty('errorDelimited');
            }
            $this->modx->toPlaceholder('message', $errors['message'], $errorPrefix);
        } else {
            $validated = true;
        }

        return $validated;
    }


    /**
     * Run any preHooks for this snippet, that allow it to stop the form as submitted
     * @return boolean
     */
    public function runPreHooks()
    {
        $validated = true;
        $preHooks = $this->getProperty('preHooks', '');
        if (!empty($preHooks)) {
            $this->loadHooks('preHooks');
            $this->preHooks->loadMultiple($preHooks, $this->dictionary->toArray(), array(
                'submitVar' => $this->getProperty('submitVar'),
                'redirectToLogin' => $this->getProperty('redirectToLogin', true, 'isset'),
                'reloadOnSuccess' => $this->getProperty('reloadOnSuccess', true, 'isset'),
            ));
            $values = $this->preHooks->getValues();
            if (!empty($values)) {
                $this->dictionary->fromArray($values);
            }

            if ($this->preHooks->hasErrors()) {
                $errors = array();
                $es = $this->preHooks->getErrors();

                $errTpl = $this->getProperty('errTpl');
                foreach ($es as $key => $error) {
                    $errors[$key] = str_replace('[[+error]]', $error, $errTpl);
                }
                $this->modx->toPlaceholders($errors, 'error');

                $errorMsg = $this->preHooks->getErrorMessage();
                $this->modx->toPlaceholder('message', $errorMsg, 'error');
                $validated = false;

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
            }
        }

        return $validated;
    }
}

return 'LupLoginUpdateProfileController';
