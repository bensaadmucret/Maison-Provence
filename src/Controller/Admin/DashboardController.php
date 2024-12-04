<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Media;
use App\Entity\MediaCollection;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\SEO;
use App\Entity\SiteConfiguration;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        // Récupération des statistiques
        $totalOrders = $this->entityManager->getRepository(Order::class)->count([]);
        $totalProducts = $this->entityManager->getRepository(Product::class)->count([]);
        $totalUsers = $this->entityManager->getRepository(User::class)->count([]);
        $totalCategories = $this->entityManager->getRepository(Category::class)->count([]);

        // Récupération des dernières commandes
        $latestOrders = $this->entityManager->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Création du graphique des ventes
        $chart = $this->createSalesChart();

        return $this->render('admin/dashboard.html.twig', [
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_users' => $totalUsers,
            'total_categories' => $totalCategories,
            'latest_orders' => $latestOrders,
            'sales_chart' => $chart,
        ]);
    }

    private function createSalesChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        // Récupération des ventes des 30 derniers jours
        $endDate = new \DateTime();
        $startDate = (new \DateTime())->modify('-30 days');

        // Récupération de toutes les commandes payées dans la période
        $orders = $this->entityManager->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->andWhere('o.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getResult();

        $dates = [];
        $totals = [];
        $currentDate = clone $startDate;

        // Initialisation des tableaux avec des 0 pour tous les jours
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('d/m');
            $totals[$dateStr] = 0;
            $currentDate->modify('+1 day');
        }

        // Remplissage avec les vraies données
        /** @var Order $order */
        foreach ($orders as $order) {
            $dateStr = $order->getCreatedAt()->format('Y-m-d');
            if (isset($totals[$dateStr])) {
                $totals[$dateStr] += $order->getTotal();
            }
        }

        $chart->setData([
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Ventes (€)',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                    'borderColor' => 'rgb(251, 191, 36)',
                    'data' => array_values($totals),
                    'tension' => 0.4,
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value + ' €'; }",
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Maison Provence')
            ->setTranslationDomain('messages')
            ->renderContentMaximized();
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $siteConfig = $this->entityManager->getRepository(SiteConfiguration::class)->findOneBy([]);
        $avatarUrl = $siteConfig && $siteConfig->getLogo() ? '/uploads/logo/' . $siteConfig->getLogo() : null;

        return parent::configureUserMenu($user)
            ->setAvatarUrl($avatarUrl)
            ->setName($user->getUserIdentifier());
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('menu.clients');
        yield MenuItem::linkToCrud('client.list', 'fas fa-users', User::class);
        
        yield MenuItem::section('menu.products');
        yield MenuItem::linkToCrud('Catégories', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-tag', Product::class);
        
        yield MenuItem::section('menu.orders');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-shopping-cart', Order::class);
        
        yield MenuItem::section('menu.settings');
        yield MenuItem::linkToCrud('SEO', 'fas fa-search', SEO::class);
        yield MenuItem::linkToCrud('Configuration', 'fas fa-cog', SiteConfiguration::class);
        yield MenuItem::linkToCrud('Médias', 'fas fa-images', Media::class);
        yield MenuItem::linkToCrud('Collections', 'fas fa-folder', MediaCollection::class);
        yield MenuItem::linkToCrud('Équipe', 'fas fa-users', TeamMember::class);

        yield MenuItem::section('');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'app_home');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('css/admin.css')
            ->addJsFile('js/admin.js');
    }
}
