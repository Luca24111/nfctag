<?php

namespace App\Entity;

use App\Repository\ProdottoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProdottoRepository::class)]
class Prodotto
{
    #[ORM\Id]
    // mantieni GeneratedValue con strategia IDENTITY (MySQL ti permette di fornire manualmente l'id, altrimenti lo genera)
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

     #[ORM\Column(type: 'integer', nullable: true, unique: true)]
    private ?int $nfcId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column]
    private ?bool $avaiable = null;

    #[ORM\Column(name: "is_out")]
    private ?bool $out = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $update_date = null;

    private ?string $image = null;

    #[ORM\ManyToMany(targetEntity: Eventi::class, inversedBy: 'prodotti')]
    #[ORM\JoinTable(name: 'prodotti_eventi')]
    private Collection $eventi;


    public function __construct()
    {
        $this->eventi = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // NUOVO setter per ID
    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getNfcId(): ?int
    {
        return $this->nfcId;
    }

    public function setNfcId(?int $nfcId): static
    {
        $this->nfcId = $nfcId;
        return $this;
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

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isAvaiable(): ?bool
    {
        return $this->avaiable;
    }

    public function setAvaiable(bool $avaiable): static
    {
        $this->avaiable = $avaiable;

        return $this;
    }

    public function isOut(): ?bool
    {
        return $this->out;
    }

    public function setOut(bool $out): static
    {
        $this->out = $out;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }


    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTimeInterface $update_date): static
    {
        $this->update_date = $update_date;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
         return $this;
    }
    
            public function getEventi(): Collection
    {
        return $this->eventi;
    }

    public function addEvento(Eventi $evento): self
    {
        if (! $this->eventi->contains($evento)) {
            $this->eventi->add($evento);
            // mantiene anche l'inverse side
            $evento->addProdotto($this);
        }
        return $this;
    }

    public function removeEvento(Eventi $evento): self
    {
        if ($this->eventi->removeElement($evento)) {
            $evento->removeProdotto($this);
        }
        return $this;
    }
}
