<?php

class lup
{
    public $modx;

    protected $request;
    public $initialized = array();
    public $chunks = array();

    function __construct(modX &$modx, array $config = array())
    {
        $this->modx =& $modx;

        $corePath = $this->modx->getOption('lup_core_path', $config, $this->modx->getOption('core_path') . 'components/loginup/');
        $assetsUrl = $this->modx->getOption('lup_assets_url', $config, $this->modx->getOption('assets_url') . 'components/loginup/');
        $accesstype = $this->modx->getOption('lup_accesstype', '', 'jpg, jpeg, png, gif');

        $connectorUrl = $assetsUrl . 'connector.php';

        $default = $modx->getOption('lup_default', null, MODX_ASSETS_URL . 'components/loginup/images/default.png', true);
        if (preg_match("@^http://@i", $default)) {
            $default = MODX_ASSETS_URL . $default;
        }

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/loginup/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/mgr/',
            'default' => $default,
            'accesstype' => $accesstype,

            'actionUrl' => $assetsUrl . 'action.php',
            'frontend_js' => '[[+assetsUrl]]js/web/default.js',
            'lexicons' => '',
        ), $config);

        $this->modx->addPackage('loginup', $this->config['modelPath']);
        $this->modx->lexicon->load('loginup:default');
    }

    public function getUploadFolder()
    {
        $uploaddir = $this->modx->getOption('lup_directory', '', "user_photos", 1);

        //correct the folder
        if (!preg_match('/\/$/', $uploaddir)) {
            $uploaddir = $uploaddir . '/';
        }
        if (preg_match('/^\//', $uploaddir)) {
            $uploaddir = preg_replace('/^\//', '', $uploaddir);
        }
        return $uploaddir;
    }

    public function removeUserPhoto($user_id)
    {

        $uploaddir_full = MODX_BASE_PATH . $this->getUploadFolder();
        $user_files = glob($uploaddir_full . "user_$user_id.*");

        foreach ($user_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }


    public function loadJsCssWeb($objectName = 'lup')
    {

        if ($js = trim($this->config['frontend_js'])) {
            if (preg_match('/\.js/i', $js)) {
                $this->modx->regClientScript(str_replace('[[+assetsUrl]]', $this->config['assetsUrl'], $js));
            }
        }

        $config = $this->modx->toJSON(array(
            'actionUrl' => str_replace('[[+assetsUrl]]', $this->config['assetsUrl'], $this->config['actionUrl']),
            'removeMessage' => $this->modx->lexicon('lup_message_photo_remove'),
            'selector' => '.lup_photo_remove_btn',
        ));
        $objectName = trim($objectName);
        $this->modx->regClientScript(
            "<script type=\"text/javascript\">{$objectName}.initialize({$config});</script>", true
        );
    }

    public function loadJsCssMgr()
    {
        $this->modx->controller->addHTML("
                <script>
                    lup['config'] = {$this->modx->toJSON($this->config)};
                </script>
            ");
        $this->modx->controller->addJavascript(MODX_ASSETS_URL . 'components/loginup/js/mgr/default.js');
    }

    public function loadLexicon()
    {
        $lexicons = $this->config['lexicons'];

        if ($lexicons) {
            $lexicons = explode(',', $lexicons);
            $lexicons = array_map('trim', $lexicons);
            foreach ($lexicons as $lexicon) {
                $this->modx->lexicon->load($lexicon);
            }
        }
    }
}