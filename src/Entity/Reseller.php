<?php

namespace App\Entity;

use App\Repository\ResellerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ResellerRepository::class)
 * @UniqueEntity("email")
 * @OA\Schema(
 *      description="Reseller model",
 *      title="Reseller",
 * )
 */
class Reseller
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"show_resellers"})
     * @OA\Property(
     *     format="int64",
     *     description="ID",
     *     title="ID",
     * )
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"show_resellers"})
     * @OA\Property(
     *     description="reseller Username",
     *     title="Username",
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"show_resellers"})
     * @OA\Property(
     *     description="reseller Email",
     *     title="Email",
     * )
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity=Customer::class, mappedBy="reseller", orphanRemoval=true)
     * @Groups({"show_resellers"})
     */
    private $customer;

    public function __construct()
    {
        $this->customer = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|Customer[]
     */
    public function getCustomer(): Collection
    {
        return $this->customer;
    }

    public function addCustomer(Customer $customer): self
    {
        if (!$this->customer->contains($customer)) {
            $this->customer[] = $customer;
            $customer->setReseller($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): self
    {
        if ($this->customer->contains($customer)) {
            $this->customer->removeElement($customer);
            // set the owning side to null (unless already changed)
            if ($customer->getReseller() === $this) {
                $customer->setReseller(null);
            }
        }

        return $this;
    }
}
