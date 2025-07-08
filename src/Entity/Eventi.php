<?php

namespace App\Entity;

use App\Repository\EventiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventiRepository::class)]
class Eventi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nome = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $data = null;

    #[ORM\Column(length: 255)]
    private ?string $citta = null;

    #[ORM\ManyToMany(targetEntity: Prodotto::class, mappedBy: 'eventi')]
    private Collection $prodotti;

    public function __construct()
    {
        $this->prodotti = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function setNome(string $nome): static
    {
        $this->nome = $nome;

        return $this;
    }

    public function getData(): ?\DateTimeInterface
    {
        return $this->data;
    }

    public function setData(\DateTimeInterface $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getCitta(): ?string
    {
        return $this->citta;
    }

    public function setCitta(string $citta): static
    {
        $this->citta = $citta;

        return $this;
    }

    /**
     * @return Collection|Prodotto[]
     */
    public function getProdotti(): Collection
    {
        return $this->prodotti;
    }

    public function addProdotto(Prodotto $prodotto): self
    {
        if (! $this->prodotti->contains($prodotto)) {
            $this->prodotti->add($prodotto);
        }
        return $this;
    }

    public function removeProdotto(Prodotto $prodotto): self
    {
        $this->prodotti->removeElement($prodotto);
        return $this;
    }
}
