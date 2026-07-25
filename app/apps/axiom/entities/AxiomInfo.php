<?php

declare(strict_types=1);

namespace App\Entity;

use Axiom\Entity\Annotation as ORM;

/**
 * AxiomInfo Entity - информация о компонентах Axiom ORM
 */
#[ORM\Entity(table: 'axiom_info')]
class AxiomInfo
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer', autoIncrement: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'version', type: 'string', length: 50, default: '')]
    private string $version = '';

    #[ORM\Column(name: 'category', type: 'string', length: 50, default: 'core')]
    private string $category = 'core';

    #[ORM\Column(name: 'sort_order', type: 'integer', default: 0)]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTime $updatedAt = null;

    // ========== Getters and Setters ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return $this->title . ' (' . $this->version . ')';
    }
}
