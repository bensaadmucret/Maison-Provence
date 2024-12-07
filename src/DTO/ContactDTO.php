<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ContactDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Veuillez entrer votre nom')]
        #[Assert\Length(
            min: 2,
            max: 255,
            minMessage: 'Votre nom doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Votre nom ne peut pas contenir plus de {{ limit }} caractères'
        )]
        private ?string $name = null,

        #[Assert\NotBlank(message: 'Veuillez entrer votre email')]
        #[Assert\Email(message: 'Veuillez entrer une adresse email valide')]
        private ?string $email = null,

        #[Assert\NotBlank(message: 'Veuillez entrer un sujet')]
        #[Assert\Length(
            min: 5,
            max: 255,
            minMessage: 'Le sujet doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Le sujet ne peut pas contenir plus de {{ limit }} caractères'
        )]
        private ?string $subject = null,

        #[Assert\NotBlank(message: 'Veuillez entrer votre message')]
        #[Assert\Length(
            min: 10,
            minMessage: 'Votre message doit contenir au moins {{ limit }} caractères'
        )]
        private ?string $message = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
