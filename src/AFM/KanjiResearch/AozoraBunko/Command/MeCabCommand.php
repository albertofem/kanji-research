<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Command;

use AFM\KanjiResearch\AozoraBunko\Entity\Card;
use JpnForPhp\Helper\Helper;
use JpnForPhp\Transliterator\Kana;
use JpnForPhp\Transliterator\Romaji;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MeCabCommand extends Command
{
    protected function configure()
    {
        $this->setName('aozora:cards:mecab')
            ->addArgument("file", InputArgument::REQUIRED, "Card html file")
            ->addArgument("output", InputArgument::REQUIRED, "Output file")
            ->setDescription('Create ruby HTML file with Aozora card content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument("file");

        $card = new Card($file);
        $card->process();

        $tokenizer = new \MeCab_Tagger(['-O' => 'chasen']);
        $mecab = new \MeCab_Tagger(['-O' => 'yomi']);

        $hepburn = new Romaji();
        $hiragana = new Kana('hiragana');

        $lines = explode("\n", $card->getContent());
        $realLines = [];

        foreach($lines as $key => $line)
        {
            if(!empty($line) and strlen($line) != 0)
                $realLines[] = trim($line, "\r\s\t	");
        }

        $lineSentences = [];

        foreach($realLines as $line)
        {
            $line = explode("。", $line);

            foreach($line as $key => $linn)
            {
                if(empty($linn))
                    unset($line[$key]);
            }

            $lineSentences[] = $line;
        }

        $furiganizeSentence = function($sentence) use($mecab, $hepburn, $hiragana, $tokenizer)
        {
            $tokens = explode("\n", $tokenizer->parse($sentence));

            foreach($tokens as $key => $token)
            {
                $tokenized = explode("\t", $token);

                if(preg_match("/\p{Han}/u", $tokenized[0]))
                {
                    $sentence = preg_replace_callback("/" .$tokenized[0]. "/u", function($matches) use ($mecab, $hepburn, $hiragana, $tokenized)
                    {
                        $hiragana = $hepburn->transliterate($tokenized[1]);

                        return "<ruby><rb>" .$matches[0] . "</rb><rp>（</rp><rt>" .$hiragana. "</rt><rp>）</rp></ruby>";
                    }, $sentence);
                }
            }

            return $sentence;
        };

        foreach($lineSentences as $jkey => $sentences)
        {
            foreach($sentences as $key => $sentence)
            {
                $lineSentences[$jkey] = $furiganizeSentence($sentence);
            }
        }

        $html = @implode("。<br>", $lineSentences);

        file_put_contents($input->getArgument("output"), $html);
    }
} 