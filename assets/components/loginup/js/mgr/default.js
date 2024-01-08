var lup = function (config) {
    config = config || {};
    lup.superclass.constructor.call(this, config);
};
Ext.extend(lup, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}, ux: {}, fields: {}
});
Ext.reg('lup', lup);
lup = new lup();