<?php
/**
 * Created by PhpStorm.
 * User: honza
 * Date: 15.06.2017
 * Time: 11:55
 */

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

Tracy\Debugger::enable(\Tracy\Debugger::PRODUCTION,__DIR__.'/../log');

(new \Nette\Loaders\RobotLoader())
    ->setCacheStorage(new \Nette\Caching\Storages\DevNullStorage())
    ->addDirectory(__DIR__ . '/../app')
    ->register();