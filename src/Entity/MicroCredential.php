<?php

namespace App\Entity;

use App\Repository\MicroCredentialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MicroCredentialRepository::class)]
class MicroCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $badgeUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $level = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'microCredentials')]
    private Collection $skills;

    /**
     * @var Collection<int, StudentProgress>
     */
    #[ORM\OneToMany(targetEntity: StudentProgress::class, mappedBy: 'microCredential')]
    private Collection $studentProgress;

    #[ORM\Column(nullable: true)]
    private ?bool $isVisible = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->studentProgress = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBadgeUrl(): ?string
    {
        return $this->badgeUrl;
    }

    public function setBadgeUrl(?string $badgeUrl): static
    {
        $this->badgeUrl = $badgeUrl;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

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

    /**
     * @return Collection<int, skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(skill $skill): static
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    /**
     * @return Collection<int, StudentProgress>
     */
    public function getStudentProgress(): Collection
    {
        return $this->studentProgress;
    }

    public function addStudentProgress(StudentProgress $studentProgress): static
    {
        if (!$this->studentProgress->contains($studentProgress)) {
            $this->studentProgress->add($studentProgress);
            $studentProgress->setMicroCredential($this);
        }

        return $this;
    }

    public function removeStudentProgress(StudentProgress $studentProgress): static
    {
        if ($this->studentProgress->removeElement($studentProgress)) {
            // set the owning side to null (unless already changed)
            if ($studentProgress->getMicroCredential() === $this) {
                $studentProgress->setMicroCredential(null);
            }
        }

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(?bool $isVisible): static
    {
        $this->isVisible = $isVisible;

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
}
