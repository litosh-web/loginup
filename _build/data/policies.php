<?php
 /** @noinspection PhpIncludeInspection */
        $policies = include($this->config['elements'] . 'policies.php');
        if (!is_array($policies)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Access Policies');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => array('name'),
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['policies']),
        ];
        foreach ($policies as $name => $data) {
            if (isset($data['data'])) {
                $data['data'] = json_encode($data['data']);
            }
            /** @var $policy modAccessPolicy */
            $policy = $this->modx->newObject('modAccessPolicy');
            $policy->fromArray(array_merge(array(
                    'name' => $name,
                    'lexicon' => $this->config['name_lower'] . ':permissions',
                ), $data)
                , '', true, true);
            $vehicle = $this->builder->createVehicle($policy, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($policies) . ' Access Policies');