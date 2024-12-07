<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AddressService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AddressRepository $addressRepository,
        private Security $security,
    ) {
    }

    public function createAddress(
        string $street,
        string $city,
        string $postalCode,
        string $country,
        bool $isDefault = false,
        bool $isBillingAddress = false,
    ): Address {
        /** @var User $user */
        $user = $this->security->getUser();

        $address = new Address();
        $address->setStreet($street)
            ->setCity($city)
            ->setPostalCode($postalCode)
            ->setCountry($country)
            ->setIsDefault($isDefault)
            ->setIsBillingAddress($isBillingAddress)
            ->setUser($user);

        if ($isDefault) {
            $this->unsetDefaultAddress($user);
        }

        if ($isBillingAddress) {
            $this->unsetBillingAddress($user);
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return $address;
    }

    public function updateAddress(
        int $id,
        string $street,
        string $city,
        string $postalCode,
        string $country,
        bool $isDefault = false,
        bool $isBillingAddress = false,
    ): Address {
        /** @var User $user */
        $user = $this->security->getUser();

        $address = $this->addressRepository->find($id);

        if (!$address) {
            throw new \InvalidArgumentException('Address not found');
        }

        if ($address->getUser() !== $user) {
            throw new \InvalidArgumentException('Address does not belong to current user');
        }

        $address->setStreet($street)
            ->setCity($city)
            ->setPostalCode($postalCode)
            ->setCountry($country)
            ->setIsDefault($isDefault)
            ->setIsBillingAddress($isBillingAddress);

        if ($isDefault) {
            $this->unsetDefaultAddress($user, $address);
        }

        if ($isBillingAddress) {
            $this->unsetBillingAddress($user, $address);
        }

        $this->entityManager->flush();

        return $address;
    }

    public function deleteAddress(int $id): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $address = $this->addressRepository->find($id);

        if (!$address) {
            throw new \InvalidArgumentException('Address not found');
        }

        if ($address->getUser() !== $user) {
            throw new \InvalidArgumentException('Address does not belong to current user');
        }

        $this->entityManager->remove($address);
        $this->entityManager->flush();

        return true;
    }

    private function unsetDefaultAddress(User $user, ?Address $excludeAddress = null): void
    {
        $defaultAddress = $this->addressRepository->findOneBy([
            'user' => $user,
            'isDefault' => true,
        ]);

        if ($defaultAddress && $defaultAddress !== $excludeAddress) {
            $defaultAddress->setIsDefault(false);
            $this->entityManager->flush();
        }
    }

    private function unsetBillingAddress(User $user, ?Address $excludeAddress = null): void
    {
        $billingAddress = $this->addressRepository->findOneBy([
            'user' => $user,
            'isBillingAddress' => true,
        ]);

        if ($billingAddress && $billingAddress !== $excludeAddress) {
            $billingAddress->setIsBillingAddress(false);
            $this->entityManager->flush();
        }
    }
}
