<?php
require_once MODX_CORE_PATH . 'model/modx/processors/security/user/getlist.class.php';

class lup_getlist extends modUserGetListProcessor
{
    public function prepareQueryAfterCount(xPDOQuery $c)
    {
        $c->select($this->modx->getSelectColumns('modUser', 'modUser'));
        $c->select($this->modx->getSelectColumns('modUserProfile', 'Profile', '', array('fullname', 'email', 'blocked', 'photo')));

        $id = $this->getProperty('id', 0);
        if (!empty($id)) {
            $c->where(array(
                $this->classKey . '.id:IN' => is_string($id) ? explode(',', $id) : $id,
            ));
        }

        return $c;
    }
}

return 'lup_getlist';