#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use AFM\KanjiResearch\AozoraBunko\Command\ServerRunCommand;
use AFM\KanjiResearch\AozoraBunko\Command\ListCardsCommand;
use AFM\KanjiResearch\AozoraBunko\Command\ProcessCardCommand;
use AFM\KanjiResearch\AozoraBunko\Command\CardReadabilityCommand;
use AFM\KanjiResearch\AozoraBunko\Command\SearchReadableCardsCommand;

define('AOZORA_ROOT', __DIR__ . '/../vendor/aozorabunko/aozorabunko');

$application = new Application();
$application->add(new ServerRunCommand());
$application->add(new ListCardsCommand());
$application->add(new ProcessCardCommand());
$application->add(new CardReadabilityCommand());
$application->add(new SearchReadableCardsCommand());
$application->run();