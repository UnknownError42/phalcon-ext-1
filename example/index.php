<?php

use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View\Simple as SimpleView;

# Micro app

// In micro mode, most of the di services are the same
$di = require_once __DIR__ . '/bootstrap.php';

// However we will use simple view here
$di->setShared('view', function () {
    $view = new SimpleView;

    $view->setViewsDir($this->get('config')->toArray()['view']['dir']);
    $view->registerEngines([
        '.twig' => 'twig',
    ]);

    return $view;
});

$app = new MicroApplication($di);

$app->getRouter()->setUriSource(Router::URI_SOURCE_GET_URL);

require_once __DIR__ . '/MicroController.php';

$app->mount((new Collection)
    ->setPrefix('/')
    ->setHandler(MicroController::class, true)
    ->get('/', 'indexAction')
    ->get('db', 'dbAction')
    ->get('di', 'diAction')
    ->get('mail', 'mailAction')
    ->get('logger', 'loggerAction')
    ->get('validation', 'validationAction')
);

$app->handle();
