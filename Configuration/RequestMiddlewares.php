<?php

return [
    'frontend' => [
        'dmk/mkforms/ajax-handler' => [
            'target' => \DMK\MkForms\Middleware\AjaxHandler::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
            'before' => [
                'typo3/cms-frontend/output-compression',
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
