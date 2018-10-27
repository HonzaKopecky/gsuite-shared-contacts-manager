<?php

/*! \mainpage Google Shared Contacts API Client
 *
 * This is a Nette based application that enables G Suite administrators to manage shared
 * contacts under their G Suite domain.
 *
 * This is semestral project for BI-ZNF subject.
 * \section req Requirements
 * PHP 7 or higher.
 *
 * \section install Installation
 *
 * Make directories `temp/` and `log/` writable.
 *
 * Use composer to install dependencies: `composer install`
 *
 * **It is CRITICAL that whole `app/`, `log/` and `temp/` directories are not accessible directly
   via a web browser. See [security warning](https://nette.org/security-warning).**
 * \section test Testing
 * Tests are available in /tests directory and are built with Nette\Tester.
 *
 * \subsection unit Unit Tests
 * Units test can be prepared without further preparation.
 *
 * \subsection integration Integration Tests
 * Integration tests need to be performed after an user is authenticated.
 *
 * Open the app in browser in Developer mode and authenticate with Google.
 *
 * You will see you access token dumped in Tracy window.
 *
 * Copy his token to test/Integration/ContactServiceTest.phpt and assign it to variable $accessToken of SimulatedSessionSection
 *
 * You need to run the Integration test with valid php.ini file that enables cURL and OpenSSL.
 *
 * Then you can run the Integration test (example: vendor/bin/tester tests/Integration -c C:/xampp/php/php.ini)
 */


require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableTracy(__DIR__ . '/../log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
