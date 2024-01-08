<?php
require_once MODX_CORE_PATH . 'components/login/model/login/login.class.php';
require_once MODX_CORE_PATH . 'components/loginup/model/lup.class.php';


class luplogin extends Login
{
    public function loadController($controller)
    {
        if ($this->modx->loadClass('LoginController', $this->config['modelPath'] . 'login/', true, true)) {
            $classPath = MODX_CORE_PATH . 'components/loginup/controllers/web/' . $controller . '.php';
            $className = 'Login' . $controller . 'Controller';
            if (file_exists($classPath)) {
                if (!class_exists($className)) {
                    $className = require_once $classPath;
                }
                if (class_exists($className)) {
                    $this->controller = new $className($this, $this->config);
                } else {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[Login] Could not load controller: ' . $className . ' at ' . $classPath);
                }
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[Login] Could not load controller file: ' . $classPath);
            }
        } else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Login] Could not load LoginController class.');
        }
        return $this->controller;
    }
}

return 'luplogin';