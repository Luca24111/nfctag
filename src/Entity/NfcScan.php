<?php

namespace App\Entity;

use App\Repository\NfcScanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NfcScanRepository::class)]
#[ORM\Table(name: 'nfc_scans')]
#[ORM\Index(columns: ['device_id'], name: 'idx_device_id')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_id')]
#[ORM\Index(columns: ['timestamp'], name: 'idx_timestamp')]
#[ORM\Index(columns: ['sync_status'], name: 'idx_sync_status')]
class NfcScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $deviceId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $nfcId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $productName = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['in', 'out', 'unknown'])]
    private ?string $productStatus = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['read', 'write', 'register'])]
    private ?string $scanType = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $timestamp = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deviceInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['synced', 'received', 'pending', 'error'])]
    private ?string $syncStatus = 'pending';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function setDeviceId(string $deviceId): static
    {
        $this->deviceId = $deviceId;
        return $this;
    }

    public function getNfcId(): ?string
    {
        return $this->nfcId;
    }

    public function setNfcId(string $nfcId): static
    {
        $this->nfcId = $nfcId;
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductStatus(): ?string
    {
        return $this->productStatus;
    }

    public function setProductStatus(string $productStatus): static
    {
        $this->productStatus = $productStatus;
        return $this;
    }

    public function getScanType(): ?string
    {
        return $this->scanType;
    }

    public function setScanType(string $scanType): static
    {
        $this->scanType = $scanType;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getDeviceInfo(): ?string
    {
        return $this->deviceInfo;
    }

    public function setDeviceInfo(?string $deviceInfo): static
    {
        $this->deviceInfo = $deviceInfo;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getSyncStatus(): ?string
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(string $syncStatus): static
    {
        $this->syncStatus = $syncStatus;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->deviceId,
            'nfc_id' => $this->nfcId,
            'product_name' => $this->productName,
            'product_status' => $this->productStatus,
            'scan_type' => $this->scanType,
            'timestamp' => $this->timestamp?->format('c'),
            'user_id' => $this->userId,
            'device_info' => $this->deviceInfo,
            'location' => $this->location,
            'sync_status' => $this->syncStatus,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c')
        ];
    }
}

