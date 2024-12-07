<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddressDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    private ?string $street = null;

    private ?string $streetComplement = null;

    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Length(min: 5, max: 5)]
    private ?string $zipCode = null;

    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    private ?string $city = null;

    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    private ?string $country = null;

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    private ?string $phone = null;

    private bool $isDefault = false;

    // Getters
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getStreetComplement(): ?string
    {
        return $this->streetComplement;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    // Setters
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function setStreetComplement(?string $streetComplement): self
    {
        $this->streetComplement = $streetComplement;

        return $this;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }
}
