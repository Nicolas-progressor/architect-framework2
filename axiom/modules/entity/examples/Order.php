<?php

declare(strict_types=1);

namespace App\Entity;

use Axiom\Entity\Annotation as ORM;

/**
 * Order entity
 */
#[ORM\Entity(table: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer', autoIncrement: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId = 0;

    #[ORM\Column(name: 'order_number', type: 'string', length: 50, unique: true)]
    private string $orderNumber = '';

    #[ORM\Column(name: 'total', type: 'decimal', precision: 10, scale: 2)]
    private float $total = 0;

    #[ORM\Column(name: 'status', type: 'string', length: 50, default: 'pending')]
    private string $status = 'pending';

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTime $updatedAt = null;

    // Relationship
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders', joinColumn: 'user_id')]
    private ?User $user = null;

    // ========== Getters and Setters ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    // ========== Business Methods ==========

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function complete(): void
    {
        $this->status = 'completed';
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
    }
}
