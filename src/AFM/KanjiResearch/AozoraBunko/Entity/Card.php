<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Entity;

use JpnForPhp\Helper\Helper;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Process;

class Card
{
    protected $file;

    protected $title;

    protected $author;

    protected $content;

    protected $kanjiFrequency;

    protected $cacheDir;

    protected $cardFile;

    protected $processed = false;

    protected $obi2Grade = null;

    protected $obi2Bigframe = null;

    public static function createFromFile($file)
    {
        $cardFile = __DIR__ . '/../../../../../cards/' . md5($file) . '.json';

        if(file_exists($cardFile))
        {
            return unserialize(file_get_contents($cardFile));
        }

        return new Card($file);
    }

    public function __construct($file)
    {
        $this->file = (string) $file;

        $this->cacheDir = __DIR__ . '/../../../../../cache/';
        $this->cardFile = __DIR__ . '/../../../../../cards/' . md5($file) . '.json';
    }

    public function process()
    {
        if($this->processed)
            return;

        if(!file_exists($this->file))
            throw new InvalidArgumentException("This card does not have a file associated!");

        $this->file = $this->convertFileToUtf8($this->file);

        $content = file_get_contents($this->file);

        $crawler = new Crawler($content);

        $titleXPath = CssSelector::toXPath("h1.title");
        $authorXPath = CssSelector::toXPath("h2.author");

        $this->title = @$crawler->filterXPath($titleXPath)->getNode(0)->textContent;
        $this->author = @$crawler->filterXPath($authorXPath)->getNode(0)->textContent;
        $this->content = strip_tags($content);

        $this->processed = true;

        $this->getKanjiFrequency();

        file_put_contents($this->cardFile, serialize($this));
    }

    /**
     * @param mixed $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getKanjiFrequency()
    {
        if(!$this->kanjiFrequency)
        {
            $characters = Helper::extractKanjiCharacters($this->getContent());
            $characters = array_count_values($characters);

            arsort($characters);

            $this->kanjiFrequency = $characters;
        }

        return $this->kanjiFrequency;
    }

    public function getCommonKanjis($kanjis)
    {
        $kanjisContained = $this->getKanjiFrequency();

        $commonKanjis = [];

        foreach($kanjisContained as $kanji => $frequency)
        {
            foreach($kanjis as $knownKanji)
            {
                if($knownKanji == $kanji)
                {
                    $commonKanjis[$knownKanji] = $frequency;
                }
            }
        }

        return $commonKanjis;
    }

    public function getReadabilityScore($kanjis)
    {
        $commonKanjis = $this->getCommonKanjis($kanjis);

        $notKnow = array_diff_key($this->getKanjiFrequency(), $commonKanjis);

        $totalReadableKanjis = count($commonKanjis);
        $totalNotKnownKanjis = count($notKnow);
        $totalReadableOcurrences = array_sum($commonKanjis);
        $totalNotKnownOcurrences = array_sum($notKnow);
        $totalOcurrences = $totalReadableOcurrences + $totalNotKnownOcurrences;
        $totalKanjis = $totalReadableKanjis + $totalNotKnownKanjis;

        $percentageNotKnowKanjis = round(($totalNotKnownKanjis*100) / $totalKanjis, 2);
        $percentageNotKnowOcurrences = round(($totalNotKnownOcurrences*100) / $totalOcurrences, 2);

        $average = round(100-(($percentageNotKnowOcurrences + $percentageNotKnowKanjis) / 2), 2);

        return [
            'readability' => $average,
            'total_readable_kanjis' => $totalReadableKanjis,
            'total_readable_ocurrences' => $totalReadableOcurrences,
            'total_notknow_kanjis' => $totalNotKnownKanjis,
            'total_notknow_ocurrences' => $totalNotKnownOcurrences,
            'total_kanjis' => $totalKanjis,
            'total_ocurrences' => $totalOcurrences,
            'percentage_notknow_kanjis' => $percentageNotKnowKanjis,
            'percentage_notknow_ocurrences' => $percentageNotKnowOcurrences
        ];
    }

    public function getTotalCharacters()
    {
        return count($this->getKanjiFrequency());
    }

    private function convertFileToUtf8($file)
    {
        $convertedFile = $this->cacheDir . md5($this->file) . ".html";

        if(file_exists($convertedFile))
            return $convertedFile;

        $process = "iconv -f SHIFT-JIS -t UTF-8 '" .$file. "' > '" .$convertedFile. "'";
        $process = new Process($process);

        $process->run();

        return $convertedFile;
    }
} 