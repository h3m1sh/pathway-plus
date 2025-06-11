<?php

namespace App\Entity;

use App\Repository\StudentProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProgressRepository::class)]
class StudentProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateEarned = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verifiedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne(inversedBy: 'studentProgress')]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'studentProgress')]
    private ?MicroCredential $microCredential = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEarned(): ?\DateTimeImmutable
    {
        return $this->dateEarned;
    }

    public function setDateEarned(\DateTimeImmutable $dateEarned): static
    {
        $this->dateEarned = $dateEarned;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVerifiedBy(): ?string
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?string $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getMicroCredential(): ?MicroCredential
    {
        return $this->microCredential;
    }

    public function setMicroCredential(?MicroCredential $microCredential): static
    {
        $this->microCredential = $microCredential;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null || 
               in_array($this->status, ['Completed', 'Verified']);
    }

    public function getProgressPercentage(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        return match($this->status) {
            'Completed' => 100,
            'Verified' => 100,
            'In Progress' => 75,
            'Under Review' => 50,
            default => 0
        };
    }
}
