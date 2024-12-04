<?php

namespace App\Service;

use App\DTO\AddressDTO;
use App\Entity\Address;

class DTOMapper
{
    public function toAddressDTO(Address $address): AddressDTO
    {
        $dto = new AddressDTO();
        $dto->setName($address->getName())
            ->setFirstName($address->getFirstName())
            ->setLastName($address->getLastName())
            ->setStreet($address->getStreet())
            ->setStreetComplement($address->getStreetComplement())
            ->setZipCode($address->getZipCode())
            ->setCity($address->getCity())
            ->setCountry($address->getCountry())
            ->setPhone($address->getPhone())
            ->setIsDefault($address->isDefault());

        return $dto;
    }

    public function toAddress(AddressDTO $dto, ?Address $address = null): Address
    {
        $address = $address ?? new Address();
        $address->setName($dto->getName())
                ->setFirstName($dto->getFirstName())
                ->setLastName($dto->getLastName())
                ->setStreet($dto->getStreet())
                ->setStreetComplement($dto->getStreetComplement())
                ->setZipCode($dto->getZipCode())
                ->setCity($dto->getCity())
                ->setCountry($dto->getCountry())
                ->setPhone($dto->getPhone())
                ->setIsDefault($dto->isDefault());

        return $address;
    }
}
