<?php

namespace App\Entity;

use App\Repository\NurseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NurseRepository::class)]
class Nurse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $user = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /**
     * @var Collection<int, NurseCredential>
     */
    #[ORM\OneToMany(targetEntity: NurseCredential::class, mappedBy: 'nurse', orphanRemoval: true)]
    private Collection $credentials;

    public function __construct()
    {
        $this->credentials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(string $user): static
    {
        $this->user = $user;

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

    /**
     * @return Collection<int, NurseCredential>
     */
    public function getCredentials(): Collection
    {
        return $this->credentials;
    }

    public function addCredential(NurseCredential $credential): static
    {
        if (!$this->credentials->contains($credential)) {
            $this->credentials->add($credential);
            $credential->setNurse($this);
        }

        return $this;
    }

    public function removeCredential(NurseCredential $credential): static
    {
        if ($this->credentials->removeElement($credential)) {
            // set the owning side to null (unless already changed)
            if ($credential->getNurse() === $this) {
                $credential->setNurse(null);
            }
        }

        return $this;
    }
}
