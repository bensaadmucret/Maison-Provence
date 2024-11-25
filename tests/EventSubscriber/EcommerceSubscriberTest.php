<?php

namespace App\Tests\EventSubscriber;

use App\Entity\SiteConfiguration;
use App\EventSubscriber\EcommerceSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EcommerceSubscriberTest extends TestCase
{
    private EcommerceSubscriber $subscriber;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private SiteConfiguration $siteConfig;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->siteConfig = new SiteConfiguration();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->createConfigRepository());

        $this->urlGenerator->expects($this->any())
            ->method('generate')
            ->with('app_home')
            ->willReturn('/');

        $this->subscriber = new EcommerceSubscriber(
            $this->entityManager,
            $this->security,
            $this->urlGenerator
        );
    }

    public function testEcommerceRouteIsBlockedWhenDisabled(): void
    {
        $this->siteConfig->setIsEcommerceEnabled(false);

        $event = $this->createRequestEvent('/shop/products');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testAdminCanAccessEcommerceWhenDisabled(): void
    {
        $this->siteConfig->setIsEcommerceEnabled(false);

        $event = $this->createRequestEvent('/shop/products');

        $this->security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    private function createRequestEvent(string $path): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);

        return new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private function createConfigRepository()
    {
        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($this->siteConfig);

        return $repository;
    }
}
