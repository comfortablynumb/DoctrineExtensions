<?php

namespace Searchable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Gedmo\Searchable
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     * @Gedmo\Searchable(indexed=true, stored=true, processors={
     *    @Gedmo\SearchableProcessor(class="Gedmo\Searchable\Processor\Filter\TrimFilter"),
     *    @Gedmo\SearchableProcessor(class="Gedmo\Searchable\Processor\Tokenizer\DelimiterTokenizer")
     * })
     */
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
