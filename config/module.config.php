<?php

namespace OrcidConnector;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'controllers' => [
        'invokables' => [
            'OrcidConnector\Controller\Index' => Controller\IndexController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'orcidconnector' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/orcidconnector',
                            'defaults' => [
                                '__NAMESPACE__' => 'OrcidConnector\Controller',
                                'controller' => 'Index',
                                'action' => 'authenticate',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
        ],
    ]
];
