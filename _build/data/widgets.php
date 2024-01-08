<?php
/** @noinspection PhpIncludeInspection */
        $widgets = include($this->config['elements'] . 'widgets.php');
        if (!is_array($widgets)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Dashboard Widgets');

            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['widgets']),
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        foreach ($widgets as $name => $data) {
            /** @var modDashboardWidget $widget */
            $widget = $this->modx->newObject('modDashboardWidget');
            $widget->fromArray(array_merge([
                'name' => $name,
                'namespace' => 'core',
                'lexicon' => 'core:dashboards',
            ], $data), '', true, true);
            $vehicle = $this->builder->createVehicle($widget, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($widgets) . ' Dashboard Widgets');