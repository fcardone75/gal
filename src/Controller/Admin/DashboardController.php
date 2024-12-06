<?php

namespace App\Controller\Admin;

use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\ApplicationImport;
use App\Entity\ApplicationImportTemplate;
use App\Entity\AtecoCode;
use App\Entity\Bank;
use App\Entity\BankLeasing;
use App\Entity\Confidi;
use App\Entity\ReportImport;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    private $mfaManager;

    public function __construct(EntityManagerInterface $entityManager, ?GoogleAuthenticatorInterface $googleAuthenticator = null)
    {
        $this->entityManager = $entityManager;
        $this->mfaManager = $googleAuthenticator;
    }

    #[Route('/admin/2fa_verify', methods: [Request::METHOD_POST])]
    public function post2FAAction(Request $request)
    {
        $user = $this->getUser();
        $user->setGoogleAuthenticatorSecret($request->get('secret'));
        $code = $request->get('_auth_code');
        if ($this->mfaManager->checkCode($user, $code)) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            return $this->redirect($adminUrlGenerator->setController(ApplicationCrudController::class)->generateUrl());
        }
        $secret = $this->mfaManager->generateSecret();
        $user = $this->getUser();
        $user->setGoogleAuthenticatorSecret($secret);
        $qrCodeContent = $this->mfaManager->getQRContent($user);
        return $this->render('admin/2fa-enable.html.twig', [

            'qrCodeContent' => $qrCodeContent,
            'secret' => $secret,
            'error' => $this->translator->trans('code_invalid', [], 'SchebTwoFactorBundle')

        ]);
    }

    /**
     * @Route("/", name="admin")
     */
    public function index(
        AdminUrlGenerator $adminUrlGenerator = null,
        Request           $request = null
    ): Response
    {
        $user = $this->getUser();

//        if(!$this->getUser()->isGoogleAuthenticatorEnabled()) {
        if (!$user->isGoogleAuthenticatorEnabled()) {
            if ($this->mfaManager != null) {

                $secret = $this->mfaManager->generateSecret();
//            $user = $this->getUser();
                $user->setGoogleAuthenticatorSecret($secret);
                $qrCodeContent = $this->mfaManager->getQRContent($user);
                return $this->render('admin/2fa-enable.html.twig', [
                    "qrCodeContent" => $qrCodeContent,
                    "secret" => $secret
                ]);
            }
        }

//TODO: in base a ruolo è visibile o meno la dashboard (grafici)
        if (!$this->isGranted('ROLE_ACCESS_DASHBOARD')) {
            switch (true) {
                case $this->isGranted('ROLE_ADMIN_SECURITY'):
                    // redirect to users index
                    return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->setAction(Action::INDEX)->generateUrl());
                    break;
                case $this->isGranted('ROLE_ADMIN'):
                    // redirect to confidi index
                    return $this->redirect($adminUrlGenerator->setController(ConfidiCrudController::class)->setAction(Action::INDEX)->generateUrl());
                    break;
                default:
                    return $this->render('@EasyAdmin/welcome.html.twig', [
                        'dashboard_controller_filepath' => (new \ReflectionClass(static::class))->getFileName(),
                        'dashboard_controller_class' => (new \ReflectionClass(static::class))->getShortName(),
                    ]);
            }

        }
//TODO: verificare criteri selezione confidi in base a utente loggato
        $confidiSelected = $request->request->get('confidi') ?: null;
        $isConfidiUser = $user instanceof User && $user->getConfidi();

        $criteria = [];
        $orderBy = ['businessName' => 'ASC'];

        if ($isConfidiUser) {
            $confidiSelected = $user->getConfidi()->getId();
            $criteria['id'] = $confidiSelected;
        }

        $confidiList = $this->entityManager->getRepository(Confidi::class)->findBy($criteria, $orderBy);

        $confidiData = [];

        if (!$isConfidiUser) {
            $item = [];
            $item['id'] = null;
            $item['selected'] = null;
            $item['label'] = 'Tutti';
            $confidiData[] = $item;
        }

        foreach ($confidiList as $confidi) {
            $item = [];
            $item['id'] = $confidi->getId();
            $item['selected'] = $confidi->getId() == $confidiSelected;
            $item['label'] = $confidi->getBusinessName();
            $confidiData[] = $item;
        }


//TODO: costruzione dinamica dati x charts

//    4 tipologie di strumenti
//    - Riassicurazione
//    - Abbuono di commissioni di garanzia
//    - Contributo interessi/canoni
//    - Contributo a fondo perduto

//    - Riassicurazione: totale record senza 'richiesta Richiesta Abbuono Commissioni', senza 'Richiesta Contributo Interessi/Canoni', senza 'Richiesta Contributo Fondo Perduto'
//    - Abbuono di commissioni di garanzia: totale record con 'richiesta Richiesta Abbuono Commissioni'
//    - Contributo interessi/canoni: totale record con 'Richiesta Contributo Interessi/Canoni'
//    - Contributo a fondo perduto: totale record con 'Richiesta Contributo Fondo Perduto'
        $chartConfig = [
            [
                'chart_title' => 'Riassicurazione',
                'chart_type' => 'Pie',
                'chart_contribution_type' => null
            ],
            [
                'chart_title' => 'Abbuono di commissioni di garanzia',
                'chart_type' => 'Pie',
                'chart_contribution_type' => AdditionalContribution::TYPE_ABB
            ],
            [
                'chart_title' => 'Contributo interessi/canoni',
                'chart_type' => 'Pie',
                'chart_contribution_type' => AdditionalContribution::TYPE_CON
            ],
            [
                'chart_title' => 'Contributo a fondo perduto',
                'chart_type' => 'Pie',
                'chart_contribution_type' => AdditionalContribution::TYPE_CFP
            ],
        ];

        //TODO: verificare status: application or additionalContribution?

        //    La torta dovrà essere suddivisa in 3 parti e composta dalle pratiche che hanno i seguenti stati:
        //    - In lavorazione
        //    - Deliberata positiva
        //    - Deliberata negativa

        //'00100': 'In istruttoria'
        //'00101': 'Esito positivo, in attesa di delibera CTR'
        //'00102': 'Esito Negativo, in attesa di delibera CTR'
        //'00103': 'Deliberata Negativa'
        //'00104': 'Deliberata Positiva, in attesa di antimafia'
        //'00300': 'Deliberata Positiva'
        //'00305': 'Liquidata'
        //'00306': 'Liquidata parzialmente'
        //'00307': 'Revocata'
        //'00308': 'Parzialmente Revocata'
        //'00310': 'Recupero in corso'
        //'00311': 'Recupero in corso da parte di Equitalia'
        //'00312': 'Recupero parzialmente restituito'
        //'00315': 'Recupero Restituito'
        $counterConfig = [
            [
                'code' => 0,
                'label' => 'In lavorazione',
                'statuses' => [Application::STATUS_NSIA_00100] //'00100': 'In istruttoria'
//                'statuses' => ['linked', 'created'] //'00100': 'In istruttoria'
            ],
            [
                'code' => 1,
                'label' => 'Delibera positiva',
                'statuses' => [Application::STATUS_NSIA_00104] //'00104': 'Deliberata Positiva'
            ],
            [
                'code' => 2,
                'label' => 'Delibera negativa',
                'statuses' => [Application::STATUS_NSIA_00103] //'00103': 'Deliberata Negativa'
            ],
        ];

        $chartData = [];
        // In case of empty Confidi
        // the chart data should be null
        // to display a message indicating the absence of data
        $presence_data = false;

        foreach ($chartConfig as $config_chart) {

            $criteria = [];
            if (!empty($confidiSelected)) {
                $criteria['confidi'] = $confidiSelected;
            }

            if (isset($config_chart['chart_contribution_type'])) {
                $criteria['contribution_type'] = $config_chart['chart_contribution_type'];
            }

            $applicationList = $this->entityManager->getRepository(Application::class)->findAllForDashboard($criteria);

            $chart = [];
            $labels = [];
            $series = [];
            $total = 0;

            foreach ($counterConfig as $idx_cfg => $config_counter) {
                $counter = array_filter($applicationList, function ($applicationList) use ($config_counter) {
//                    return $applicationList->getStatus() == $config_counter['statuses'];
                    return in_array($applicationList->getStatus(), $config_counter['statuses']);
                });

                $tot_tmp = count($counter);
                if (!$presence_data && $tot_tmp > 0) {
                    $presence_data = true;
                }

                if ($tot_tmp > 0) {
                    $total += $tot_tmp;
                    $series[] = ['value' => $tot_tmp, 'className' => $this->setChartPieClassFromData($config_counter['code'])];
                    $labels[] = $config_counter['label'] . ' [' . $tot_tmp . ']';
                }
            }


            $chart['type'] = $config_chart['chart_type'];
            $chart['title'] = $config_chart['chart_title'] . ' [' . $total . ']';
            $chart['labels'] = json_encode($labels);
            $chart['series'] = json_encode($series);

            $chartData[] = $chart;
        }

        return $this->render('@EasyAdmin/dashboard.html.twig', [
            'dashboard_controller_filepath' => (new \ReflectionClass(static::class))->getFileName(),
            'dashboard_controller_class' => (new \ReflectionClass(static::class))->getShortName(),
            'chartData' => $presence_data ? $chartData : [],
            'confidiData' => $confidiData,
            'isConfidiUser' => $isConfidiUser,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/images/logo.png"/>')
            ->disableDarkMode();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::subMenu('crud.application_import.plural', 'fas fa-upload')->setSubItems([
            MenuItem::linkToCrud('crud.application_import.plural', 'fas fa-upload', ApplicationImport::class)
                ->setPermission('ROLE_APPLICATION_IMPORT_INDEX'),
            MenuItem::linkToCrud('crud.application_import_template.plural', 'fas fa-file-excel', ApplicationImportTemplate::class)
                ->setPermission('ROLE_APPLICATION_IMPORT_TEMPLATE_INDEX')
        ])->setPermission('ROLE_APPLICATION_IMPORT_MENU_ITEM');
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home')
            ->setPermission('ROLE_ACCESS_DASHBOARD');
        yield MenuItem::linkToCrud('crud.report_import.plural', 'fa fa-table', ReportImport::class)
            ->setPermission('ROLE_REPORT_INDEX');
        yield MenuItem::linkToCrud('crud.application_group.plural', 'fa fa-copy', ApplicationGroup::class)
            ->setPermission('ROLE_APPLICATION_GROUP_INDEX');
        yield MenuItem::linkToCrud('crud.application.plural', 'fa fa-file', Application::class)
            ->setPermission('ROLE_APPLICATION_INDEX');
        yield MenuItem::subMenu('crud.settings', 'fas fa-cog')
            ->setPermission('ROLE_ACCESS_SETTING')
            ->setSubItems([
                MenuItem::linkToCrud('crud.users.plural', 'fas fa-users', User::class)
                    ->setPermission('ROLE_MANAGE_USER_INDEX'),
                MenuItem::linkToCrud('crud.confidi.plural', 'fas fa-building', Confidi::class)
                    ->setPermission('ROLE_CONFIDI_INDEX'),
                MenuItem::linkToCrud('crud.banks.plural', 'fas fa-university', Bank::class)
                    ->setPermission('ROLE_BANK_INDEX'),
                MenuItem::linkToCrud('crud.banks_leasing.plural', 'fas fa-university', BankLeasing::class)
                    ->setPermission('ROLE_BANK_LEASING_INDEX'),
                MenuItem::linkToCrud('crud.ateco_codes.plural', 'fas fa-barcode', AtecoCode::class)
                    ->setPermission('ROLE_ATECO_CODE_INDEX')
            ]);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->addMenuItems([
                MenuItem::linkToRoute('Change Password', 'fa fa-key', 'security_change_password')
            ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('css/admin.css')
            ->addJsFile('chartist-js/chartist.min.js')
            ->addCssFile('chartist-js/chartist.min.css');
    }

    private function setChartPieClassFromData($status_code)
    {
        switch ($status_code) {
            case 0:
                return "ct-series-working";
            case 1:
                return "ct-series-positive";
            case 2:
                return "ct-series-negative";
            default:
                return "";
        }

    }
}
