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
use Symfony\Component\Console\Output\OutputInterface;

class CardReadabilityCommand extends Command
{
    protected function configure()
    {
        $this->setName('aozora:cards:readability')
            ->addArgument("file", InputArgument::REQUIRED, "Card html file")
            ->addArgument("kanjis", InputArgument::REQUIRED)
            ->setDescription('Shows readability report')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument("file");

        $card = new Card($file);
        $card->process();

        $output->writeln("Title: <info>" .$card->getTitle(). "</info>");
        $output->writeln("Author: <info>" .$card->getAuthor(). "</info>");

        $kanjiFrequency = $card->getKanjiFrequency();

        $output->writeln("Found <info>" .count($kanjiFrequency). "</info> different kanjis\n");

        $commonKanjis = $card->getCommonKanjis(Helper::extractKanjiCharacters($input->getArgument("kanjis")));

        $output->write("Common Kanjis: ");

        foreach($commonKanjis as $kanji => $frequency)
        {
            $output->write("<info>" .$kanji. "</info>(" .$frequency. ") ");
        }

        $score = $card->getReadabilityScore(Helper::extractKanjiCharacters($input->getArgument("kanjis")));

        $output->writeln("\n\nReadability score: <info>" .$score['readability']. "</info>");
        $output->writeln("Percentage not know Kanjis: <info>" . $score['percentage_notknow_kanjis']. "%</info>");
        $output->writeln("Percentage not know ocurrences: <info>" . $score['percentage_notknow_ocurrences']. "%</info>\n");
    }
} 