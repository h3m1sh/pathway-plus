<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $studentId = null;

    /**
     * @var Collection<int, StudentProgress>
     */
    #[ORM\OneToMany(targetEntity: StudentProgress::class, mappedBy: 'student')]
    private Collection $studentProgress;

    /**
     * @var Collection<int, JobRole>
     */

    #[ORM\ManyToMany(targetEntity: JobRole::class, inversedBy: 'interestedStudents')]
    #[ORM\JoinTable(name: 'user_job_role_interests')]
    private Collection $jobRoleInterests;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: 'user_skills')]
    private Collection $skills;

    /**
     * @var Collection<int, MicroCredential>
     */
    #[ORM\ManyToMany(targetEntity: MicroCredential::class)]
    #[ORM\JoinTable(name: 'user_micro_credentials')]
    private Collection $microCredentials;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastProfileUpdate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $careerGoal = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $careerGoalUpdatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->studentProgress = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
        $this->jobRoleInterests = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->microCredentials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
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

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(?string $studentId): static
    {
        $this->studentId = $studentId;
        return $this;
    }

    // Helper methods for roles
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function isStudent(): bool
    {
        return in_array('ROLE_STUDENT', $this->roles) || (!$this->isAdmin() && empty($this->roles));
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
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
            $studentProgress->setStudent($this);
        }

        return $this;
    }

    public function removeStudentProgress(StudentProgress $studentProgress): static
    {
        if ($this->studentProgress->removeElement($studentProgress)) {
            // set the owning side to null (unless already changed)
            if ($studentProgress->getStudent() === $this) {
                $studentProgress->setStudent(null);
            }
        }

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

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

    /**
     * @return Collection<int, JobRole>
     */
    public function getJobRoleInterests(): Collection
    {
        return $this->jobRoleInterests;
    }

    public function addJobRoleInterest(JobRole $jobRole): static
    {
        if (!$this->jobRoleInterests->contains($jobRole)) {
            $this->jobRoleInterests->add($jobRole);
        }
        return $this;
    }

    public function removeJobRoleInterest(JobRole $jobRole): static
    {
        $this->jobRoleInterests->removeElement($jobRole);
        return $this;
    }

    public function getLastProfileUpdate(): ?string
    {
        return $this->lastProfileUpdate;
    }

    public function setLastProfileUpdate(?string $lastProfileUpdate): static
    {
        $this->lastProfileUpdate = $lastProfileUpdate;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCareerGoal(): ?string
    {
        return $this->careerGoal;
    }

    public function setCareerGoal(?string $careerGoal): static
    {
        $this->careerGoal = $careerGoal;
        $this->careerGoalUpdatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCareerGoalUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->careerGoalUpdatedAt;
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
        }

        return $this;
    }

    public function removeMicroCredential(MicroCredential $microCredential): static
    {
        $this->microCredentials->removeElement($microCredential);

        return $this;
    }
}
