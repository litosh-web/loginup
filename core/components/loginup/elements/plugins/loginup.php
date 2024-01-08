<?php
$namespace = $modx->controller->config['namespace'];
$cnr = $modx->controller->config['controller'];

if ($namespace != "core" || !preg_match('/security\/user.*/i', $cnr)) {
    return;
}

$lup = $modx->getService('lup', 'lup', MODX_CORE_PATH . 'components/loginup/model/');
$lup = new lup($modx);
$lup->loadJsCssMgr();

$modx->controller->addLexiconTopic('loginup:default');

switch ($modx->event->name) {
    case "OnUserFormPrerender":

        if ($namespace != "core" || !in_array($cnr, array("security/user/update", "security/user/create"))) {
            return;
        }
        $lup_customize_profile = $modx->getOption('lup_customize_profile');
        if (!$lup_customize_profile) {
            return;
        }

        //узнать про путь к картинке
        $data['photo'] = htmlspecialchars($user->Profile->photo);
        $data['photo_preview'] = ($data['photo']) ? "/" . $data['photo'] . '?ver=' . time() : $lup->config['default'];

        $modx->regClientCSS(MODX_ASSETS_URL . 'components/loginup/css/user-page.css');

        $modx->controller->addHtml("<script type='text/javascript'>
        Ext.ComponentMgr.onAvailable('modx-user-tabs', function () {
        this.on('beforerender', function () {

            var accesstype = lup.config.accesstype.replace(/\s/g, '').split(',');
            
            var rightCol = this.items.items[0].items.items[0].items.items[1];
            if (this.ownerCt.getForm().findField('photo')) {
                this.ownerCt.getForm().findField('photo').destroy();
            }

            rightCol.items.insert(1, 'modx-photo', new Ext.Container({
                xtype: 'container',
                layout: 'form',
                labelAlign: 'top',
                items: [{
                    xtype: 'container',
                    fieldLabel: _('lup_photo_preview'),
                    id: 'modx-photo-preview',
                    width: 200,
                    height: 200,
                    autoEl: {
                        tag: 'img',
                        src: '" . $data['photo_preview'] . "'
                    }

                }, {
                    xtype: 'modx-combo-browser',                    
                    id: 'modx-photo',
                    name: 'photo',                    
                    fieldLabel: _('lup_photo_upload'),
                    value: '" . $data['photo'] . "',
                    changePhoto: function (data) {
//                        if (!accesstype.includes(data.ext)) {
//                            MODx.msg.alert('Ошибка!','Расширение не поддерживается');
//                            return false;
//                        }
                        
                        this.setValue(data.fullRelativeUrl);
                        var preview_field = Ext.getCmp('modx-photo-preview');
                        preview_field.el.dom.src = (data.fullRelativeUrl) ? '/' + data.fullRelativeUrl : lup.config['default'];
                    },
                    listeners: {
                        select: {
                            fn: function (data) {
                                this.changePhoto(data);
                            }
                        }
                    }
                }, {
                    xtype: 'box',
                    width: 200,
                    autoEl: {tag: 'hr'},
                    style: {'margin-bottom': '10px'}
                }]
            }));
        });
    });
</script>
            ");

        break;
    case 'OnManagerPageBeforeRender':

        if ($namespace != "core" || $cnr != "security/user") {
            return;
        }
        $lup_customize_grid = $modx->getOption('lup_customize_grid');
        if (!$lup_customize_grid) {
            return;
        }

        $controller->addLastJavascript(MODX_ASSETS_URL . 'components/loginup/js/mgr/grids/modx-grid-user.js');

        break;
}