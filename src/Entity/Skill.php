<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $difficulty = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, MicroCredential>
     */
    #[ORM\ManyToMany(targetEntity: MicroCredential::class, mappedBy: 'skills')]
    private Collection $microCredentials;

    /**
     * @var Collection<int, JobRole>
     */
    #[ORM\ManyToMany(targetEntity: JobRole::class, mappedBy: 'skills')]
    private Collection $jobRoles;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->microCredentials = new ArrayCollection();
        $this->jobRoles = new ArrayCollection();
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): static
    {
        $this->difficulty = $difficulty;

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
     * @return Collection<int, MicroCredential>
     */
    public function getMicroCredentials(): Collection
    {
        return $this->microCredentials;
    }

    public function addMicroCredential(MicroCredential $microCredential): static
    {
        if (!$this->microCredentials->contains($microCredential)) {
            $this->microCredentials->add($microCredential);
            $microCredential->addSkill($this);
        }

        return $this;
    }

    public function removeMicroCredential(MicroCredential $microCredential): static
    {
        if ($this->microCredentials->removeElement($microCredential)) {
            $microCredential->removeSkill($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, JobRole>
     */
    public function getJobRoles(): Collection
    {
        return $this->jobRoles;
    }

    public function addJobRole(JobRole $jobRole): static
    {
        if (!$this->jobRoles->contains($jobRole)) {
            $this->jobRoles->add($jobRole);
            $jobRole->addSkill($this);
        }

        return $this;
    }

    public function removeJobRole(JobRole $jobRole): static
    {
        if ($this->jobRoles->removeElement($jobRole)) {
            $jobRole->removeSkill($this);
        }

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
