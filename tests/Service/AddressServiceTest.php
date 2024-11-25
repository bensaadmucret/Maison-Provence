<?php

namespace App\Tests\Service;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AddressServiceTest extends TestCase
{
    private AddressService $addressService;
    private EntityManagerInterface|MockObject $entityManager;
    private AddressRepository|MockObject $addressRepository;
    private Security|MockObject $security;
    private User $user;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->addressRepository = $this->createMock(AddressRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->user = new User();
        $this->user->setEmail('test@example.com');

        $this->security->method('getUser')->willReturn($this->user);

        $this->addressService = new AddressService(
            $this->entityManager,
            $this->addressRepository,
            $this->security
        );
    }

    public function testCreateAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setStreet('123 Test Street')
            ->setCity('Test City')
            ->setPostalCode('12345')
            ->setCountry('FR');

        $this->addressRepository
            ->expects(self::once())
            ->method('count')
            ->with(['user' => $this->user])
            ->willReturn(0);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($address);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $result = $this->addressService->createAddress($address);

        // Assert
        self::assertSame($this->user, $result->getUser());
        self::assertTrue($result->isDefault());
    }

    public function testCreateAddressNotFirstAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setStreet('123 Test Street')
            ->setCity('Test City')
            ->setPostalCode('12345')
            ->setCountry('FR');

        $this->addressRepository
            ->expects(self::once())
            ->method('count')
            ->with(['user' => $this->user])
            ->willReturn(1);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($address);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $result = $this->addressService->createAddress($address);

        // Assert
        self::assertFalse($result->isDefault());
    }

    public function testUpdateAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setUser($this->user)
            ->setStreet('123 Test Street')
            ->setCity('Test City')
            ->setPostalCode('12345')
            ->setCountry('FR');

        $this->addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($address);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $result = $this->addressService->updateAddress(1, $address);

        // Assert
        self::assertSame($address, $result);
    }

    public function testUpdateAddressWrongUser(): void
    {
        // Arrange
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');

        $address = new Address();
        $address->setUser($otherUser);

        $this->addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($address);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update address: address does not belong to current user');

        // Act
        $this->addressService->updateAddress(1, $address);
    }

    public function testDeleteAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setUser($this->user)
            ->setIsDefault(false);

        $this->addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($address);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($address);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $this->addressService->deleteAddress(1);
    }

    public function testDeleteDefaultAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setUser($this->user)
            ->setIsDefault(true);

        $otherAddress = new Address();
        $otherAddress->setUser($this->user)
            ->setIsDefault(false);

        $this->addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($address);

        $this->addressRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $this->user, 'isDefault' => false])
            ->willReturn($otherAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($address);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $this->addressService->deleteAddress(1);

        // Assert
        self::assertTrue($otherAddress->isDefault());
    }

    public function testSetDefaultAddress(): void
    {
        // Arrange
        $address = new Address();
        $address->setUser($this->user)
            ->setIsDefault(false);

        $currentDefault = new Address();
        $currentDefault->setUser($this->user)
            ->setIsDefault(true);

        $this->addressRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($address);

        $this->addressRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['user' => $this->user, 'isDefault' => true])
            ->willReturn($currentDefault);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        // Act
        $result = $this->addressService->setDefaultAddress(1);

        // Assert
        self::assertTrue($result->isDefault());
        self::assertFalse($currentDefault->isDefault());
    }

    public function testGetUserAddresses(): void
    {
        // Arrange
        $addresses = [
            new Address(),
            new Address(),
        ];

        $this->addressRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['user' => $this->user])
            ->willReturn($addresses);

        // Act
        $result = $this->addressService->getUserAddresses();

        // Assert
        self::assertSame($addresses, $result);
    }
}
