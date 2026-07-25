<?php

declare(strict_types=1);

namespace App\Entity;

use Axiom\Entity\Annotation as ORM;

/**
 * User entity
 */
#[ORM\Entity(table: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer', autoIncrement: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    private string $email = '';

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private string $password = '';

    #[ORM\Column(name: 'status', type: 'string', length: 50, default: 'active')]
    private string $status = 'active';

    #[ORM\Column(name: 'role', type: 'string', length: 50, default: 'user')]
    private string $role = 'user';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private ?\DateTime $updatedAt = null;

    // Relationships
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private array $orders = [];

    #[ORM\ManyToMany(targetEntity: Role::class, pivotTable: 'user_roles', localKey: 'user_id', foreignKey: 'role_id')]
    private array $roles = [];

    // ========== Getters and Setters ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
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

    // ========== Business Methods ==========

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }
}
