<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Command;

use AFM\KanjiResearch\AozoraBunko\Entity\Card;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCardCommand extends Command
{
    protected function configure()
    {
        $this->setName('aozora:cards:process')
            ->addArgument("file", InputArgument::REQUIRED, "Card html file")
            ->setDescription('List all Aozora Bunk cards')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument("file");

        $card = new Card($file);
        $card->process();

        $output->writeln("Title: <info>" .$card->getTitle(). "</info>");
        $output->writeln("Author: <info>" .$card->getAuthor(). "</info>");
        $output->writeln("Content: <info>" .$card->getContent(). "</info>");
    }
} 