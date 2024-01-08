<?php
/** @noinspection PhpIncludeInspection */
        $policy_templates = include($this->config['elements'] . 'policy_templates.php');
        if (!is_array($policy_templates)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Policy Templates');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => array('name'),
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['policy_templates']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
                'Permissions' => array(
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['permission']),
                    xPDOTransport::UNIQUE_KEY => array('template', 'name'),
                ),
            ),
        ];
        foreach ($policy_templates as $name => $data) {
            $permissions = array();
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                foreach ($data['permissions'] as $name2 => $data2) {
                    /** @var $permission modAccessPermission */
                    $permission = $this->modx->newObject('modAccessPermission');
                    $permission->fromArray(array_merge(array(
                            'name' => $name2,
                            'description' => $name2,
                            'value' => true,
                        ), $data2)
                        , '', true, true);
                    $permissions[] = $permission;
                }
            }
            /** @var $permission modAccessPolicyTemplate */
            $permission = $this->modx->newObject('modAccessPolicyTemplate');
            $permission->fromArray(array_merge(array(
                    'name' => $name,
                    'lexicon' => $this->config['name_lower'] . ':permissions',
                ), $data)
                , '', true, true);
            if (!empty($permissions)) {
                $permission->addMany($permissions);
            }
            $vehicle = $this->builder->createVehicle($permission, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($policy_templates) . ' Access Policy Templates');