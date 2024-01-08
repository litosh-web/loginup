var _prototype = Ext.ComponentMgr.types["modx-grid-user"];

lup.grid.users = function (config) {
    this.sm = new Ext.grid.CheckboxSelectionModel();

    Ext.applyIf(config, {
        url: lup.config['connectorUrl'],
        fields: ['id', 'username', 'fullname', 'email', 'gender', 'blocked', 'role', 'active', 'cls', 'photo'],
        sm: this.sm
        , columns: [this.sm, {
            header: _('id')
            , dataIndex: 'id'
            , width: 50
            , sortable: true
        }, {
            header: _('lup_photo')
            , dataIndex: 'photo',
            renderer: function (val, p, record) {
                var img = '';
                if (val['length'] > 0) {
                    if (!/(jpg|jpeg|png|gif|bmp)$/.test(val.toLowerCase())) {
                        return val;
                    } else if (/^(http|https)/.test(val)) {
                        img = val;
                    } else {
                        img = MODx.config['connectors_url'] + 'system/phpthumb.php?&src=' + val + '&wctx=web&h=40&w=40&zc=1';
                    }
                } else {
                    val = lup.config['default'];
                    img = MODx.config['connectors_url'] + 'system/phpthumb.php?&src=' + val + '&wctx=web&h=40&w=40&zc=0&f=png';
                }
                return String.format('<div style="margin:-13px -18px -13px -5px"><img src="{0}"></div>', img);
            }

        }, {
            header: _('name')
            , dataIndex: 'username'
            , width: 150
            , sortable: true
            , renderer: function (value, p, record) {
                return String.format('<a href="?a=security/user/update&id={0}" title="{1}" class="x-grid-link">{2}</a>', record.id, _('user_update'), Ext.util.Format.htmlEncode(value));
            }
        }, {
            header: _('user_full_name')
            , dataIndex: 'fullname'
            , width: 180
            , sortable: true
            , editor: {xtype: 'textfield'}
            , renderer: Ext.util.Format.htmlEncode
        }, {
            header: _('email')
            , dataIndex: 'email'
            , width: 180
            , sortable: true
            , editor: {xtype: 'textfield'}
        }, {
            header: _('active')
            , dataIndex: 'active'
            , width: 80
            , sortable: true
            , editor: {xtype: 'combo-boolean', renderer: 'boolean'}
        }, {
            header: _('user_block')
            , dataIndex: 'blocked'
            , width: 80
            , sortable: true
            , editor: {xtype: 'combo-boolean', renderer: 'boolean'}
        }]
    });
    lup.grid.users.superclass.constructor.call(this, config);
};
Ext.extend(lup.grid.users, _prototype, {});
Ext.reg('modx-grid-user', lup.grid.users);