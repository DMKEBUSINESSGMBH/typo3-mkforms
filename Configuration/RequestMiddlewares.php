<?php

return [
    'frontend' => [
        'dmk/mkforms/ajax-handler' => [
            'target' => \DMK\MkForms\Middleware\AjaxHandler::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
            'before' => [
                'typo3/cms-frontend/output-compression',
            ],
        ],
        'dmk/mkforms/header-injection' => [
            'target' => \DMK\MkForms\Middleware\HeaderInjection::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
