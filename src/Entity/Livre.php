<?php

namespace App\Entity;

use App\Repository\LivreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: LivreRepository::class)]
class Livre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $resume = null;


    #[ORM\Column]
    private ?int $quantiteEnStock = null; // REMPLACE qte + empruntDisponible

    #[ORM\Column]
    private ?float $pu = null;

    #[ORM\Column]
    private ?int $isbn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datepub = null;

    // --- CHAMPS EMPRUNT ---
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fraisEmprunt = null; // ex: 20.00 DT

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $caution = null; // ex: 50.00 DT (remboursable)

    #[ORM\Column]
    private int $dureeMaxEmprunt = 14; // en jours

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $empruntDisponible = false;

// src/Entity/Livre.php

#[ORM\Transient] // optionnel, juste pour indiquer que ce n'est pas persisté en DB
private bool $hasEmpruntEnCours = false;


    // --- RELATIONS ---
    #[ORM\ManyToOne(targetEntity: Editeur::class, inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Editeur $editeur = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'livres')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToMany(targetEntity: Auteur::class, inversedBy: 'livres')]
    private Collection $auteurs;


    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: CommandeItem::class)]
private Collection $commandeItems;

#[ORM\OneToMany(mappedBy: 'livre', targetEntity: Avis::class, orphanRemoval: true)]
private Collection $avis;


#[ORM\OneToMany(mappedBy: 'livre', targetEntity: Promotion::class)]
private Collection $promotions;

    public function __construct()
    {
        $this->auteurs = new ArrayCollection();
        $this->commandeItems = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->promotions = new ArrayCollection(); 
    }

    // --- GETTERS & SETTERS ---
    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }

    public function getQuantiteEnStock(): ?int { return $this->quantiteEnStock; }
    public function setQuantiteEnStock(int $quantiteEnStock): self { $this->quantiteEnStock = $quantiteEnStock; return $this; }

    public function getPu(): ?float { return $this->pu; }
    public function setPu(float $pu): self { $this->pu = $pu; return $this; }

    public function getIsbn(): ?int { return $this->isbn; }
    public function setIsbn(int $isbn): self { $this->isbn = $isbn; return $this; }

    public function getDatepub(): ?\DateTimeInterface { return $this->datepub; }
    public function setDatepub(\DateTimeInterface $datepub): self { $this->datepub = $datepub; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }

    public function getFraisEmprunt(): ?string { return $this->fraisEmprunt; }
    public function setFraisEmprunt(?string $fraisEmprunt): self { $this->fraisEmprunt = $fraisEmprunt; return $this; }

    public function getCaution(): ?string { return $this->caution; }
    public function setCaution(?string $caution): self { $this->caution = $caution; return $this; }

    public function getDureeMaxEmprunt(): int { return $this->dureeMaxEmprunt; }
    public function setDureeMaxEmprunt(int $dureeMaxEmprunt): self { $this->dureeMaxEmprunt = $dureeMaxEmprunt; return $this; }

    // --- RELATIONS ---
    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): self { $this->categorie = $categorie; return $this; }

    public function getEditeur(): ?Editeur { return $this->editeur; }
    public function setEditeur(?Editeur $editeur): self { $this->editeur = $editeur; return $this; }

    public function getAuteurs(): Collection { return $this->auteurs; }
    public function addAuteur(Auteur $auteur): self
    {
        if (!$this->auteurs->contains($auteur)) {
            $this->auteurs->add($auteur);
        }
        return $this;
    }
    public function removeAuteur(Auteur $auteur): self
    {
        $this->auteurs->removeElement($auteur);
        return $this;
    }

    // Méthode utile pour les formulaires
    public function __toString(): string
    {
        return $this->titre ?? 'Livre sans titre';
    }

    // Méthode pratique pour Twig : est-ce qu'on peut emprunter ?
    public function isEmpruntPossible(): bool
    {
        return $this->quantiteEnStock > 0;
    }

    // Getter
public function isEmpruntDisponible(): bool
{
    return $this->empruntDisponible;
}

// Setter
public function setEmpruntDisponible(bool $empruntDisponible): self
{
    $this->empruntDisponible = $empruntDisponible;
    return $this;
}

public function getCommandeItems(): Collection
{
    return $this->commandeItems;
}

public function addCommandeItem(CommandeItem $commandeItem): self
{
    if (!$this->commandeItems->contains($commandeItem)) {
        $this->commandeItems->add($commandeItem);
        $commandeItem->setLivre($this);
    }

    return $this;
}

public function removeCommandeItem(CommandeItem $commandeItem): self
{
    $this->commandeItems->removeElement($commandeItem);

    return $this;
}


// Méthodes pratiques
public function getAvis(): Collection { return $this->avis; }

public function getRatingMoyen(): float
{
    if ($this->avis->isEmpty()) return 0.0;

    $total = 0;
    foreach ($this->avis as $avis) {
        $total += $avis->getNote();
    }
    return round($total / $this->avis->count(), 1);
}

public function getNombreAvis(): int
{
    return $this->avis->count();
}


// Dans src/Entity/Livre.php – ajoute cette méthode

public function getPrixActuel(): float
{
    $now = new \DateTime();

    // Recherche une promotion active
    foreach ($this->promotions as $promo) {
        if (
            $promo->isActive() &&
            $promo->getDateDebut() <= $now &&
            $promo->getDateFin() >= $now &&
            (
                $promo->getLivre() === $this ||
                $promo->getCategorie() === $this->getCategorie() ||
                $promo->getEditeur() === $this->getEditeur()
            )
        ) {
            $reduction = (float)$this->getPu() * ((float)$promo->getPourcentage() / 100);
            return round((float)$this->getPu() - $reduction, 2);
        }
    }

    return (float)$this->getPu(); // prix normal
}

public function getPourcentageReduction(): ?int
{
    $now = new \DateTime();
    foreach ($this->promotions as $promo) {
        if (
            $promo->isActive() &&
            $promo->getDateDebut() <= $now &&
            $promo->getDateFin() >= $now &&
            (
                $promo->getLivre() === $this ||
                $promo->getCategorie() === $this->getCategorie() ||
                $promo->getEditeur() === $this->getEditeur()
            )
        ) {
            return (int)$promo->getPourcentage();
        }
    }
    return null;
}

public function setHasEmpruntEnCours(bool $value): self
{
    $this->hasEmpruntEnCours = $value;
    return $this;
}

public function getHasEmpruntEnCours(): bool
{
    return $this->hasEmpruntEnCours;
}

// Pour Twig tu peux aussi ajouter :
public function isHasEmpruntEnCours(): bool
{
    return $this->hasEmpruntEnCours;
}
public function getResume(): ?string
{
    return $this->resume;
}

public function setResume(?string $resume): self
{
    $this->resume = $resume;
    return $this;
}


}