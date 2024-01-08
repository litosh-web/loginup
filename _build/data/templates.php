<?php
/** @noinspection PhpIncludeInspection */
        $templates = include($this->config['elements'] . 'templates.php');
        if (!is_array($templates)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Templates');

            return;
        }
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Templates'] = [
            xPDOTransport::UNIQUE_KEY => 'templatename',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['templates']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        $objects = [];
        foreach ($templates as $name => $data) {
            /** @var modTemplate[] $objects */
            $objects[$name] = $this->modx->newObject('modTemplate');
            $objects[$name]->fromArray(array_merge([
                'templatename' => $name,
                'description' => $data['description'],
                'content' => $this::_getContent($this->config['core'] . 'elements/templates/' . $data['file'] . '.tpl'),
                'static' => !empty($this->config['static']['templates']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/templates/' . $data['file'] . '.tpl',
            ], $data), '', true, true);
        }
        $this->category->addMany($objects);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($objects) . ' Templates');