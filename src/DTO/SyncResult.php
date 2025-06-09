<?php

namespace App\DTO;

class SyncResult
{
    private int $totalProcessed = 0;
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $failed = 0;
    private int $archived = 0;
    private array $errors = [];
    private \DateTime $startTime;
    private \DateTime $endTime;

    public function __construct()
    {
        $this->startTime = new \DateTime();
    }

    public function finish(): void
    {
        $this->endTime = new \DateTime();
    }

    public function incrementCreated(): void
    {
        $this->created++;
        $this->totalProcessed++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
        $this->totalProcessed++;
    }

    public function incrementSkipped(): void
    {
        $this->skipped++;
        $this->totalProcessed++;
    }

    public function incrementFailed(string $identifier, string $error): void
    {
        $this->failed++;
        $this->totalProcessed++;
        $this->errors[] = [
            'identifier' => $identifier,
            'error' => $error,
            'timestamp' => new \DateTime()
        ];
    }

    public function incrementArchived(int $count = 1): void
    {
        $this->archived += $count;
    }

    // Getters
    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getArchived(): int
    {
        return $this->archived;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime ?? null;
    }

    public function getDuration(): ?\DateInterval
    {
        if (!$this->endTime) {
            return null;
        }
        return $this->startTime->diff($this->endTime);
    }

    public function isSuccessful(): bool
    {
        return $this->failed === 0;
    }

    public function getSummary(): array
    {
        return [
            'total_processed' => $this->totalProcessed,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'archived' => $this->archived,
            'success_rate' => $this->totalProcessed > 0 ?
                round((($this->totalProcessed - $this->failed) / $this->totalProcessed) * 100, 2) : 0,
            'duration' => $this->getDuration()?->format('%H:%I:%S'),
            'has_errors' => !empty($this->errors)
        ];
    }

    public function __toString(): string
    {
        $summary = $this->getSummary();

        return sprintf(
            'Sync completed: %d processed (%d created, %d updated, %d skipped, %d failed, %d archived) in %s',
            $summary['total_processed'],
            $summary['created'],
            $summary['updated'],
            $summary['skipped'],
            $summary['failed'],
            $summary['archived'],
            $summary['duration'] ?? 'unknown time'
        );
    }
}
