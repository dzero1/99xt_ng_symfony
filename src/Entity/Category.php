<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=BookCategory::class, mappedBy="categoryId")
     */
    private $categoryBooks;

    public function __construct()
    {
        $this->bookCategories = new ArrayCollection();
        $this->categoryBooks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|BookCategory[]
     */
    public function getCategoryBooks(): Collection
    {
        return $this->categoryBooks;
    }

    public function addCategoryBook(BookCategory $categoryBook): self
    {
        if (!$this->categoryBooks->contains($categoryBook)) {
            $this->categoryBooks[] = $categoryBook;
            $categoryBook->setCategory($this);
        }

        return $this;
    }

    public function removeCategoryBook(BookCategory $categoryBook): self
    {
        if ($this->categoryBooks->removeElement($categoryBook)) {
            // set the owning side to null (unless already changed)
            if ($categoryBook->getCategory() === $this) {
                $categoryBook->setCategory(null);
            }
        }

        return $this;
    }

}
