<?php

namespace IPS\toolbox\extensions\toolbox\Scanner;

use IPS\toolbox\Code\Abstracts\ScannerAbstract;

/**
 *
 */
class _Core extends ScannerAbstract
{

    public function fullStop(): array
    {
        return [
            'IPS\Content\_Comment' => 1,
            'IPS\Content\_Item' => 1,
            'IPS\Content\_Review' => 1,
            'IPS\Node\_Model' => 1,
        ];
    }

    public function autoLint(): array
    {
        return [
            'IPS\Patterns\_ActiveRecord' => [
                'getStore' => 1
            ],
            'IPS\Node\_Model' => [
                'getStore' => 1,
                'get__title' => 1,
                'formatFormValues' => 1,
                'form' => 1,
                'disabledPermissions' => 1,
                'titleFromIndexData' => 1
            ],
            'IPS\Helpers\_Form' => [
                '__construct' => 1,
                '__toString' => 1,
                'addButton' => 1,
                'customTemplate' => 1,
                'getLastUsedTab' => 1,
                'saveAsSettings' => 1,
                'values' => 1
            ],
            'IPS\Content\_Comment' => [
                'getStore' => 1,
                'contentTableTemplate' => 1,
                'titleFromIndexData' => 1
            ],
            'IPS\Content\_Item' => [
                'getStore' => 1,
                'form' => 1,
                'supportedMetaDataTypes' => 1,
                'contentTableTemplate' => 1,
                'titleFromIndexData' => 1
            ],
            'IPS\Content\_Review' => [
                'getStore' => 1,
                'titleFromIndexData' => 1
            ],
            'IPS\Helpers\Form\_FormAbstract' => [
                'formatValue' => 1,
                'getValue' => 1,
                'setValue' => 1
            ],
        ];
    }
}