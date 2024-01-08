<?php

require_once MODX_CORE_PATH . 'components/login/controllers/web/Login.php';

/**
 * Handles logging in and out of users
 *
 * @package login
 * @subpackage controllers
 */
class LupLoginLoginController extends LoginLoginController
{

    public $namespace = 'loginup';
    public $type = 'login';

    public function initialize()
    {
        $this->setDefaultProperties(array(
            'loginViaEmail' => false,
            'loginTpl' => 'lgnLoginTpl',
            'logoutTpl' => 'lgnLogoutTpl',
            'loggedinResourceId' => '',
            'loggedoutResourceId' => '',
            'loginMsg' => '',
            'logoutMsg' => '',
            'preHooks' => '',
            'tplType' => 'modChunk',
            'actionKey' => 'service',
            'loginKey' => 'login',
            'logoutKey' => 'logout',
            'errorPrefix' => 'error',
            'errTpl' => 'lgnErrTpl',
            'errTplType' => 'modChunk',
            'rememberMeKey' => 'rememberme',
            'loginContext' => $this->modx->context->get('key'),
            'contexts' => '',
            'jsonResponse' => false,
        ));

        if (!empty($_REQUEST['login_context'])) {
            $this->setProperty('loginContext', $_REQUEST['login_context']);
        }
        if (!empty($_REQUEST['add_contexts'])) {
            $this->setProperty('contexts', $_REQUEST['add_contexts']);
        }
        $this->isAuthenticated = $this->modx->user->isAuthenticated($this->getProperty('loginContext'));
    }

    /**
     * Render the logout or login form
     * @return string
     */
    public function renderForm()
    {
        $redirectToPrior = $this->getProperty('redirectToPrior', false, 'isset');
        $tpl = $this->isAuthenticated ? $this->getProperty('logoutTpl') : $this->getProperty('loginTpl');
        $actionMsg = $this->isAuthenticated
            ? $this->getProperty('logoutMsg', $this->modx->lexicon('login.logout'))
            : $this->getProperty('loginMsg', $this->modx->lexicon('login'));

        $this->modx->setPlaceholder('actionMsg', $actionMsg);
        $phs = $this->isAuthenticated ? $this->getProperties() : array_merge($this->getProperties(), $_POST);

        /* make sure to strip out logout GET parameter to prevent ghost logout */
        $logoutKey = $this->getProperty('logoutKey', 'logout');
        if (!$redirectToPrior) {
            $phs['request_uri'] = str_replace(array('?service=' . $logoutKey, '&service=' . $logoutKey, '&amp;service=' . $logoutKey), '', $_SERVER['REQUEST_URI']);
        } else {
            $phs['request_uri'] = str_replace(array('?service=' . $logoutKey, '&service=' . $logoutKey, '&amp;service=' . $logoutKey), '', $_SERVER['HTTP_REFERER']);
        }

        $phs = $this->escapePlaceholders($phs);

        /* properly build logout url */
        if ($this->isAuthenticated) {
            $phs['logoutUrl'] = $phs['request_uri'];
            $phs['logoutUrl'] .= strpos($phs['logoutUrl'], '?') ? ($this->modx->getOption('xhtml_urls', null, false) ? '&amp;' : '&') : '?';
            $phs['logoutUrl'] .= $phs['actionKey'] . '=' . $phs['logoutKey'];
            $phs['logoutUrl'] = str_replace(array('?=', '&=', '&amp;='), '', $phs['logoutUrl']);
        }

        $this->loadReCaptcha();

        if ($this->isAuthenticated && $this->getProperty('loggedinResourceId') && $this->modx->resource->get('id') != $this->getProperty('loggedinResourceId')) {
            $url = $this->modx->makeUrl($this->getProperty('loggedinResourceId'), '', '', 'full');
            $this->modx->sendRedirect($url);
        }
        if (!$this->isAuthenticated && $this->getProperty('loggedoutResourceId') && $this->modx->resource->get('id') != $this->getProperty('loggedoutResourceId')) {
            $url = $this->modx->makeUrl($this->getProperty('loggedoutResourceId'), '', '', 'full');
            $this->modx->sendRedirect($url);
        }

        return $this->login->getChunk($tpl, $phs, $this->getProperty('tplType', 'modChunk'));
    }


    /**
     * Handle a Login submission
     *
     * @return void
     */
    public function login()
    {
        /* set default POST vars if not in form */
        if (empty($_POST['login_context'])) $_POST['login_context'] = $this->getProperty('loginContext');

        if ($this->runPreLoginHooks()) {
            $response = $this->runLoginProcessor();
            /* if we've got a good response, proceed */
            if (!empty($response) && !$response->isError()) {
                $this->runPostLoginHooks($response);

                /* process posthooks for login */
                if ($this->postHooks->hasErrors()) {
                    $errors = $this->postHooks->getErrors();
                    $errorMsg = $this->postHooks->getErrorMessage();
                    // Return JSON posthook errors if requested.
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
                    $errorPrefix = $this->getProperty('errorPrefix', 'error');
                    $this->modx->toPlaceholders($errors, $errorPrefix);
                    $this->modx->toPlaceholder('message', $errorMsg, $errorPrefix);
                } else {
                    // Return JSON success response if requested.
                    if ($this->getProperty('jsonResponse')) {
                        $jsonSuccessOutput = array(
                            'type' => $this->type,
                            'namespace' => $this->namespace,
                            'success' => true,
                            'status' => 1,
                            'message' => ($this->getProperty('loginMsg')) ? $this->getProperty('loginMsg') : $this->modx->lexicon('lup_message_successfully_logged_in')
                        );
                        header('Content-Type: application/json;charset=utf-8');
                        exit($this->modx->toJSON($jsonSuccessOutput));
                    }
                    $this->redirectAfterLogin($response);
                }

                /* login failed, output error */
            } else {
                $this->checkForRedirectOnFailedAuth($response);
                $errorOutput = $this->prepareFailureMessage($response, $this->modx->lexicon('login.login_err'));
                $this->modx->setPlaceholder('errors', $errorOutput);
            }
        }
    }

    /**
     * Run any preHooks before logging in
     *
     * @return boolean
     */
    public function runPreLoginHooks()
    {
        $success = true;

        /* do pre-login hooks */
        $this->loadHooks('preHooks');
        $this->preHooks->loadMultiple($this->getProperty('preHooks', ''), $this->dictionary->toArray(), array(
            'mode' => Login::MODE_LOGIN,
        ));

        /* process prehooks */
        if ($this->preHooks->hasErrors()) {
            $errors = $this->preHooks->getErrors();
            $errorMsg = $this->preHooks->getErrorMessage();

            // Return JSON prehook errors if requested.
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
            $this->modx->toPlaceholders($errors, $this->getProperty('errorPrefix', 'error'));

            $errorOutput = $this->modx->getChunk($this->getProperty('errTpl'), array('msg' => $errorMsg));
            $this->modx->setPlaceholder('errors', $errorOutput);
            $success = false;
        }
        return $success;
    }


    /**
     * @param modProcessorResponse $response
     * @param string $defaultErrorMessage
     * @return string
     */
    public function prepareFailureMessage(modProcessorResponse $response, $defaultErrorMessage = '')
    {
        $errorOutput = '';
        $errTpl = $this->getProperty('errTpl');
        $errors = $response->getFieldErrors();
        $message = $response->getMessage();
        if (!empty($errors)) {
            // Return JSON login errors if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => $message,
                    'data' => $errors
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
            foreach ($errors as $error) {
                $errorOutput .= $this->modx->getChunk($errTpl, $error);
            }
        } elseif (!empty($message)) {
            // Return JSON error message in response if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'message' => $message
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
            $errorOutput = $this->modx->getChunk($errTpl, array('msg' => $message));
        } else {
            // Return JSON default error if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => $this->type,
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => $defaultErrorMessage
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
            $errorOutput = $this->modx->getChunk($errTpl, array('msg' => $defaultErrorMessage));
        }
        return $errorOutput;
    }


    public function logout()
    {
        /* set default REQUEST vars if not provided */
        if (empty($_REQUEST['login_context'])) $_REQUEST['login_context'] = $this->getProperty('loginContext');

        if ($this->runPreLogoutHooks()) {
            /* send to logout processor and handle response for each context */
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor('security/logout', array(
                'login_context' => $this->getProperty('loginContext', $this->modx->context->get('key')),
                'add_contexts' => $this->getProperty('contexts', ''),
            ));

            /* if successful logout */
            if (!empty($response) && !$response->isError()) {
                $this->runPostLogoutHooks($response);
                // Return JSON logout success
                if ($this->getProperty('jsonResponse') && !$this->getProperty('logoutAntiJson')) {
                    $jsonSuccessOutput = array(
                        'type' => 'logout',
                        'namespace' => $this->namespace,
                        'success' => true,
                        'status' => 1,
                        'message' => $this->getProperty('logoutMsg')
                    );
                    header('Content-Type: application/json;charset=utf-8');
                    exit($this->modx->toJSON($jsonSuccessOutput));
                }
                $this->redirectAfterLogout($response);

                /* logout failed, output error */
            } else {
                $errorOutput = $this->prepareFailureMessage($response, $this->modx->lexicon('login.logout_err'));
                $this->modx->setPlaceholder('errors', $errorOutput);
            }
        }
    }

    /**
     * @return boolean
     */
    public function runPreLogoutHooks()
    {
        $success = true;
        $this->loadHooks('preHooks');
        $this->preHooks->loadMultiple($this->getProperty('preHooks', ''), $this->dictionary->toArray(), array(
            'mode' => Login::MODE_LOGOUT,
        ));

        if ($this->preHooks->hasErrors()) {
            $errors = $this->preHooks->getErrors();
            $errorMsg = $this->preHooks->getErrorMessage();
            // Return JSON login errors if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => 'logout',
                    'namespace' => $this->namespace,
                    'success' => false,
                    'status' => 0,
                    'message' => $errorMsg,
                    'data' => $errors
                );
                header('Content-Type: application/json;charset=utf-8');
                exit($this->modx->toJSON($jsonErrorOutput));
            }
            $this->modx->toPlaceholders($errors, $this->getProperty('errorPrefix', 'error'));
            $errorOutput = $this->modx->getChunk($this->getProperty('errTpl'), array('msg' => $errorMsg));
            $this->modx->setPlaceholder('errors', $errorOutput);
            $success = false;
        }
        return $success;
    }

    /**
     * Run any post-logout hooks
     *
     * @param modProcessorResponse $response
     * @return boolean
     */
    public function runPostLogoutHooks(modProcessorResponse $response)
    {
        $success = true;

        /* do post hooks for logout */
        $postHooks = $this->getProperty('postHooks', '');
        $this->loadHooks('postHooks');
        $fields = $this->dictionary->toArray();
        $fields['response'] = $response->getObject();
        $fields['contexts'] =& $contexts;
        $fields['loginContext'] =& $loginContext;
        $fields['logoutResourceId'] =& $logoutResourceId;
        $this->postHooks->loadMultiple($postHooks, $fields, array(
            'mode' => Login::MODE_LOGOUT,
        ));

        /* log posthooks errors */
        if ($this->postHooks->hasErrors()) {
            $errors = $this->postHooks->getErrors();
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Login] Post-Hook errors: ' . print_r($errors, true));
            $errorMsg = $this->postHooks->getErrorMessage();
            if (!empty($errorMsg)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[Login] Post-Hook error: ' . $errorMsg);
            }
            $success = false;
            // Return JSON posthook logout errors if requested.
            if ($this->getProperty('jsonResponse')) {
                $jsonErrorOutput = array(
                    'type' => 'logout',
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
        return $success;
    }

    public function escapePlaceholders($val) {

        //fix error with ajaxForm
        if (is_object($val)) {
            return false;
        }

        return parent::escapePlaceholders($val);
    }

}

return 'LupLoginLoginController';
