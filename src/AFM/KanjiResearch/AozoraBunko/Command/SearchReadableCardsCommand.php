<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Command;

use AFM\KanjiResearch\AozoraBunko\Entity\Card;
use JpnForPhp\Helper\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class SearchReadableCardsCommand extends Command
{
    protected function configure()
    {
        $this->setName('aozora:cards:search_readable')
            ->addArgument("kanjis", InputArgument::REQUIRED, "Kanjis you already know")
            ->addOption("score-margin", "fm", InputOption::VALUE_REQUIRED, "Readability score margin", 80)
            ->setDescription('Search for readable cards')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Loading Aozora Bunko library...");

        $finder = new Finder();
        $finder->files()
            ->in(AOZORA_ROOT . '/cards/*/files/')
            ->ignoreUnreadableDirs()
            ->name("*_*.html");

        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, $finder->count());

        foreach($finder as $file)
        {
            $card = Card::createFromFile($file);
            $card->process();

            $score = $card->getReadabilityScore(Helper::extractKanjiCharacters($input->getArgument("kanjis")))['readability'];

            if($score >= $input->getOption("score-margin"))
            {
                $progress->clear();
                $output->writeln("Found card with readability score: <info>" .$score. "<info>: " .$file->getFilename());
                $progress->display();
            }

            $progress->advance();
        }

        $progress->finish();
    }
} 