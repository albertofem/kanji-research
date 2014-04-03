<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Command;

use AFM\KanjiResearch\AozoraBunko\Entity\Card;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ListCardsCommand extends Command
{
    protected function configure()
    {
        $this->setName('aozora:cards:list')
            ->setDescription('List all Aozora Bunk cards')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder->files()
            ->in(AOZORA_ROOT . '/cards/*/files/')
            ->ignoreUnreadableDirs()
            ->name("*_*.html");

        $total = 0;
        foreach($finder as $file)
        {
            $card = Card::createFromFile($file);
            $card->process();

            $output->writeln("Find card with name: <info>" .$file->getFilename(). "</info> - Kanji frequency: <info>" .$card->getTotalCharacters(). "</info>");
            $total++;
        }

        $output->writeln("\nTotal: <info>" .$total. "</info> cards");
    }
} 