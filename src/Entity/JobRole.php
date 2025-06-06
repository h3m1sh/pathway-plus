<?php

namespace App\Entity;

use App\Repository\JobRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobRoleRepository::class)]
class JobRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $industry = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $salaryRange = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'jobRoles')]
    private Collection $skills;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 6)]
    private ?string $anzsco = null;

    #[ORM\Column(length: 50)]
    private ?string $jobCode = null;

    #[ORM\Column(length: 50)]
    private ?string $EntryRequirements = null;

    #[ORM\Column(length: 255)]
    private ?string $JobOpportunities = null;

    #[ORM\Column(length: 10)]
    private ?string $YearsOfTraining = null;

    #[ORM\Column(length: 255)]
    private ?string $JobOpportunitiesCaption = null;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): static
    {
        $this->industry = $industry;

        return $this;
    }

    public function getSalaryRange(): ?string
    {
        return $this->salaryRange;
    }

    public function setSalaryRange(?string $salaryRange): static
    {
        $this->salaryRange = $salaryRange;

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
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);

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

    public function getAnzsco(): ?string
    {
        return $this->anzsco;
    }

    public function setAnzsco(string $anzsco): static
    {
        $this->anzsco = $anzsco;

        return $this;
    }

    public function getJobCode(): ?string
    {
        return $this->jobCode;
    }

    public function setJobCode(string $jobCode): static
    {
        $this->jobCode = $jobCode;

        return $this;
    }

    public function getEntryRequirements(): ?string
    {
        return $this->EntryRequirements;
    }

    public function setEntryRequirements(string $EntryRequirements): static
    {
        $this->EntryRequirements = $EntryRequirements;

        return $this;
    }

    public function getJobOpportunities(): ?string
    {
        return $this->JobOpportunities;
    }

    public function setJobOpportunities(string $JobOpportunities): static
    {
        $this->JobOpportunities = $JobOpportunities;

        return $this;
    }

    public function getYearsOfTraining(): ?string
    {
        return $this->YearsOfTraining;
    }

    public function setYearsOfTraining(string $YearsOfTraining): static
    {
        $this->YearsOfTraining = $YearsOfTraining;

        return $this;
    }

    public function getJobOpportunitiesCaption(): ?string
    {
        return $this->JobOpportunitiesCaption;
    }

    public function setJobOpportunitiesCaption(string $JobOpportunitiesCaption): static
    {
        $this->JobOpportunitiesCaption = $JobOpportunitiesCaption;

        return $this;
    }
}
