<?php

namespace Searchable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Searchable
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    /**
     * @ORM\Column(name="category", type="string")
     */
    private $category = 'Default Category';

    /**
     * @ORM\Column(name="visits", type="integer")
     */
    private $visits = 0;

    /**
     * @ORM\Column(name="rating", type="decimal")
     */
    private $rating;

    /**
     * @ORM\Column(name="is_published", type="boolean")
     */
    private $isPublished = false;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;


    public function __construct()
    {
        $this->setRating((double) 0);
        $this->setCreatedAt(new \DateTime());
    }

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

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setVisits($visits)
    {
        $this->visits = $visits;
    }

    public function getVisits()
    {
        return $this->visits;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = new ExtendedDateTime($createdAt->format('Y-m-d H:i:s'));
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    public function getIsPublished()
    {
        return $this->isPublished;
    }

    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    public function getRating()
    {
        return $this->rating;
    }
}

class ExtendedDateTime extends \DateTime
{
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
}