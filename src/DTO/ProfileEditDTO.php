<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProfileEditDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Veuillez entrer votre prénom')]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'Votre prénom doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Votre prénom ne peut pas contenir plus de {{ limit }} caractères'
        )]
        private ?string $firstName = null,

        #[Assert\NotBlank(message: 'Veuillez entrer votre nom')]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'Votre nom doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Votre nom ne peut pas contenir plus de {{ limit }} caractères'
        )]
        private ?string $lastName = null,

        #[Assert\NotBlank(message: 'Veuillez entrer votre email')]
        #[Assert\Email(message: 'Veuillez entrer une adresse email valide')]
        private ?string $email = null,
    ) {
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

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

    public static function createFromUser($user): self
    {
        return new self(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail()
        );
    }
}
