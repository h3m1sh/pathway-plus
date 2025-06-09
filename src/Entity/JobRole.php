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

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $anzsco = null;

    #[ORM\Column(length: 20)]
    private ?string $jobCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $entryRequirements = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jobOpportunities = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $yearsOfTraining = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jobOpportunitiesCaption = null;

    #[ORM\Column(length: 100, options: ['default' => 'careers.govt.nz'])]
    private string $source = 'careers.govt.nz';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'synced'])]
    private string $syncStatus = 'synced'; // synced, error, manual_override

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $syncError = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $manuallyEdited = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isArchived = false;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function setAnzsco(?string $anzsco): static
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
        return $this->entryRequirements;
    }

    public function setEntryRequirements(?string $entryRequirements): static
    {
        $this->entryRequirements = $entryRequirements;

        return $this;
    }

    public function getJobOpportunities(): ?string
    {
        return $this->jobOpportunities;
    }

    public function setJobOpportunities(?string $jobOpportunities): static
    {
        $this->jobOpportunities = $jobOpportunities;

        return $this;
    }

    public function getYearsOfTraining(): ?string
    {
        return $this->yearsOfTraining;
    }

    public function setYearsOfTraining(?string $yearsOfTraining): static
    {
        $this->yearsOfTraining = $yearsOfTraining;

        return $this;
    }

    public function getJobOpportunitiesCaption(): ?string
    {
        return $this->jobOpportunitiesCaption;
    }

    public function setJobOpportunitiesCaption(?string $jobOpportunitiesCaption): static
    {
        $this->jobOpportunitiesCaption = $jobOpportunitiesCaption;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): static
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function getSyncStatus(): string
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(string $syncStatus): static
    {
        $this->syncStatus = $syncStatus;

        return $this;
    }

    public function getSyncError(): ?string
    {
        return $this->syncError;
    }

    public function setSyncError(?string $syncError): static
    {
        $this->syncError = $syncError;

        return $this;
    }

    public function isManuallyEdited(): bool
    {
        return $this->manuallyEdited;
    }

    public function setManuallyEdited(bool $manuallyEdited): static
    {
        $this->manuallyEdited = $manuallyEdited;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    public function markAsSyncSuccessful(): static
    {
        $this->setSyncStatus('synced');
        $this->setSyncError(null);
        $this->setLastSyncedAt(new \DateTimeImmutable());
        $this->setUpdatedAt(new \DateTimeImmutable());

        return $this;
    }

    public function markAsSyncFailed(string $error): static
    {
        $this->setSyncStatus('error');
        $this->setSyncError($error);
        $this->setLastSyncedAt(new \DateTimeImmutable());

        return $this;
    }

    public function markAsManuallyEdited(): static
    {
        $this->setManuallyEdited(true);
        $this->setSyncStatus('manual_override');
        $this->setUpdatedAt(new \DateTimeImmutable());

        return $this;
    }
}
