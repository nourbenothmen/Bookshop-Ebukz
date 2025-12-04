<?php
// src/Entity/Emprunt.php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
class Emprunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $emprunteur = null;

    #[ORM\ManyToOne(targetEntity: Livre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEmprunt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateRetourPrevue = null;

   #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
private ?\DateTimeImmutable $dateRetourReel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $fraisEmprunt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $caution = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_cours'; // en_cours, retourne, annule
    // src/Entity/Emprunt.php → ajoute ça

#[ORM\Column(length: 255, nullable: true)]
private ?string $cinRecto = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $cinVerso = null;

#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $cinValidatedAt = null;

// AJOUTE ÇA :
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

#[ORM\Column]
private bool $cinValidated = false;



    public function __construct()
    {
        $this->dateEmprunt = new \DateTime();
    }

    public function getRetard(): ?\DateInterval
{
    if ($this->getStatut() !== 'en_cours' || !$this->getDateRetourPrevue()) {
        return null;
    }

    $now = new \DateTimeImmutable();
    if ($now <= $this->getDateRetourPrevue()) {
        return null; // Pas en retard
    }

    return $now->diff($this->getDateRetourPrevue());
}

    // Getters & Setters...
    public function getId(): ?int { return $this->id; }

    public function getEmprunteur(): ?User { return $this->emprunteur; }
    public function setEmprunteur(?User $emprunteur): self { $this->emprunteur = $emprunteur; return $this; }

    public function getLivre(): ?Livre { return $this->livre; }
    public function setLivre(?Livre $livre): self { $this->livre = $livre; return $this; }

    public function getDateEmprunt(): ?\DateTimeInterface { return $this->dateEmprunt; }
    public function setDateEmprunt(\DateTimeInterface $dateEmprunt): self { $this->dateEmprunt = $dateEmprunt; return $this; }

    public function getDateRetourPrevue(): ?\DateTimeInterface { return $this->dateRetourPrevue; }
    public function setDateRetourPrevue(\DateTimeInterface $dateRetourPrevue): self { $this->dateRetourPrevue = $dateRetourPrevue; return $this; }

    public function getDateRetourEffective(): ?\DateTimeInterface { return $this->dateRetourEffective; }
    public function setDateRetourEffective(?\DateTimeInterface $dateRetourEffective): self { $this->dateRetourEffective = $dateRetourEffective; return $this; }

    public function getFraisEmprunt(): ?string { return $this->fraisEmprunt; }
    public function setFraisEmprunt(string $fraisEmprunt): self { $this->fraisEmprunt = $fraisEmprunt; return $this; }

    public function getCaution(): ?string { return $this->caution; }
    public function setCaution(string $caution): self { $this->caution = $caution; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    public function getCinRecto(): ?string { return $this->cinRecto; }
public function setCinRecto(?string $cinRecto): self { $this->cinRecto = $cinRecto; return $this; }

public function getCinVerso(): ?string { return $this->cinVerso; }
public function setCinVerso(?string $cinVerso): self { $this->cinVerso = $cinVerso; return $this; }

public function isCinValidated(): bool { return $this->cinValidated; }
public function setCinValidated(bool $cinValidated): self { $this->cinValidated = $cinValidated; return $this; }

public function getCinValidatedAt(): ?\DateTimeInterface { return $this->cinValidatedAt; }
public function setCinValidatedAt(?\DateTimeInterface $cinValidatedAt): self { $this->cinValidatedAt = $cinValidatedAt; return $this; }

// GETTER & SETTER À AJOUTER À LA FIN
    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
    }
    public function isEnRetard(): bool
{
    return $this->getRetard() !== null;
}

public function getRetardFormate(): string
{
    $retard = $this->getRetard();
    if (!$retard) {
        return '<span class="text-success fw-bold">À l\'heure</span>';
    }

    $parts = [];
    if ($retard->d) {
        $parts[] = $retard->d . ' jour' . ($retard->d > 1 ? 's' : '');
    }
    if ($retard->h) {
        $parts[] = $retard->h . ' heure' . ($retard->h > 1 ? 's' : '');
    }
    if ($retard->i) {
        $parts[] = $retard->i . ' minute' . ($retard->i > 1 ? 's' : '');
    }

    return '<span class="text-danger fw-bold">Retard : ' . implode(' ', $parts) . '</span>';
}


public function getDateRetourReel(): ?\DateTimeImmutable
{
    return $this->dateRetourReel;
}

public function setDateRetourReel(?\DateTimeImmutable $dateRetourReel): self
{
    $this->dateRetourReel = $dateRetourReel;

    return $this;
}
}