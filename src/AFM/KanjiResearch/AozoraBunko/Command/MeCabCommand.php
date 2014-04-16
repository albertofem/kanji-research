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
        $file = $card->convertFileToUtf8($file);

        $content = file_get_contents($file);
        $content = preg_replace("/Shift_JIS/i", "utf-8", $content);
        $content = explode("\n", $content);

        $tokenizer = new \MeCab_Tagger(['-O' => 'chasen']);
        $mecab = new \MeCab_Tagger(['-O' => 'yomi']);

        $hepburn = new Romaji();
        $hiragana = new Kana('hiragana');

        $furiganizeSentence = function($sentence, $originalLine) use($mecab, $hepburn, $hiragana, $tokenizer, $output)
        {
            $output->writeln("Original sentence: <info>" .$sentence. "</info>");

            $tokens = explode("\n", $tokenizer->parse($sentence));

            var_dump($tokens);

            $offset = 0;

            foreach($tokens as $key => $token)
            {
                $tokenized = explode("\t", $token);

                // no token
                if(!isset($tokenized[1]))
                    continue;

                // no reading
                if($tokenized[0] == $tokenized[1])
                   continue;

                // no kanji
                if(!preg_match("/\p{Han}/u", $tokenized[0]))
                    continue;

                if(preg_match("/" .$tokenized[0]. ".*/u", $originalLine, $matchesOrig,  PREG_OFFSET_CAPTURE, $offset))
                {
                    $output->writeln("Pattern match: <info>" .$matchesOrig[0][0]. "</info>");

                    $offset = $matchesOrig[0][1];

                    $output->writeln("Matched kanji <info>" .$tokenized[0]. "</info> (" .$tokenized[1]. ") in offset: " . $offset);

                    $originalLine = preg_replace_callback_offset("/" .$tokenized[0]. "/u", function($matches) use ($mecab, $tokenized, &$offset, $output)
                    {
                        $output->writeln("Found replacing match for kanji: <info>". $tokenized[0]. "</info> in offset: " .$matches[0][1]);

                        if($offset != $matches[0][1])
                        {
                            $output->writeln("Ignoring...");

                            return $matches[0][0];
                        }

                        $hiragana = Helper::convertKatakanaToHiragana($tokenized[1]);

                        $output->writeln("Adding Ruby notation: <info>" . $hiragana. "</info>");

                        $replacement = "<ruby><rb>" .$matches[0][0] . "</rb><rp>（</rp><rt>" .$hiragana. "</rt><rp>）</rp></ruby>";
                        $matchOffset = mb_strlen($replacement);

                        $offset += $matchOffset;

                        $output->writeln("New offset value: " .$offset);

                        return $replacement;

                    }, $originalLine);

                    $output->writeln("---------------------");
                }
            }

            return $originalLine;
        };


        foreach($content as $key => $line)
        {
            // ignore meta tags
            if(preg_match("/<title>|<meta/iu", $line))
                continue;

            $originalLine = preg_replace("/<rb>([^<]*)<\/rb>|<rp>[^<]*<\/rp>|<rt>[^<]*<\/rt>|<\/?ruby>/ui", "$1", $line);
            $line = trim(strip_tags($originalLine));

            if(!empty($line))
            {
                // at least one kanji
                if(preg_match("/\p{Han}/u", $line))
                {
                    $content[$key] = $furiganizeSentence($line, $originalLine);
                }
            }
        }

        file_put_contents($input->getArgument("output"), implode($content));

        die();

        foreach($lineSentences as $jkey => $sentences)
        {
            foreach($sentences as $key => $line)
            {
                $lineSentences[$jkey] = $furiganizeSentence($line);
            }
        }

        $html = @implode("。<br>", $lineSentences);

        file_put_contents($input->getArgument("output"), $html);
    }
}

function preg_replace_callback_offset($pattern, $callback, $subject, $limit = -1, &$count = 0) {

    if (is_array($subject)) {
        foreach ($subject as &$subSubject) {
            $subSubject = preg_replace_callback_offset($pattern, $callback, $subSubject, $limit, $subCount);
            $count += $subCount;
        }

        return $subject;
    }

    if (is_array($pattern)) {
        foreach ($pattern as $subPattern) {
            $subject = preg_replace_callback_offset($subPattern, $callback, $subject, $limit, $subCount);
            $count += $subCount;
        }

        return $subject;
    }

    $limit = max(-1, (int)$limit);
    $count = 0;
    $offset = 0;
    $buffer = (string)$subject;

    while ($limit === -1 || $count < $limit) {
        $result = preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE, $offset);
        if (FALSE === $result) return FALSE;
        if (!$result) break;

        $pos = $matches[0][1];
        $len = strlen($matches[0][0]);
        $replace = call_user_func($callback, $matches);

        $buffer = substr_replace($buffer, $replace, $pos, $len);

        $offset = $pos + strlen($replace);

        $count++;
    }

    return $buffer;
}