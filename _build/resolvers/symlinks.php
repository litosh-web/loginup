<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/LoginUp/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/loginup')) {
            $cache->deleteTree(
                $dev . 'assets/components/loginup/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/loginup/', $dev . 'assets/components/loginup');
        }
        if (!is_link($dev . 'core/components/loginup')) {
            $cache->deleteTree(
                $dev . 'core/components/loginup/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/loginup/', $dev . 'core/components/loginup');
        }
    }
}

return true;