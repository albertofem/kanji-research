<?php

/*
 * Copyright (c) 2014 Certadia, SL
 * All rights reserved
 */

namespace AFM\KanjiResearch\AozoraBunko\Entity;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class Card
{
    protected $file;

    protected $title;

    protected $author;

    protected $content;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function process()
    {
        if(!$this->file)
            throw new InvalidArgumentException("This card does not have a file associated!");

        $crawler = new Crawler(file_get_contents($this->file));

        $titleXPath = CssSelector::toXPath("h1.title");
        $authorXPath = CssSelector::toXPath("h2.author");
        $textXPath = CssSelector::toXPath("div.main_text");

        $title = $crawler->filterXPath($titleXPath)->getNode(0)->textContent;
        $author = $crawler->filterXPath($authorXPath)->getNode(0)->textContent;
        $content = $crawler->filterXPath($textXPath)->getNode(0)->textContent;

        $this->title = iconv(mb_detect_encoding($title), "UTF-8//translit", $title);
        $this->author = iconv(mb_detect_encoding($author), "UTF-8//translit", $author);
        $this->content = strip_tags(iconv(mb_detect_encoding($content), "UTF-8//translit", $content));
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
} 