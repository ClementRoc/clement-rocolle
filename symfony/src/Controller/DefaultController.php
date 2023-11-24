<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HomeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="app_home", methods="GET")
     */
    public function home(HomeService $homeService): Response
    {
        return $this->render('home.html.twig', [
            'experiences' => $homeService->getExperiences(),
            'portfolios'  => $homeService->getPortfolios()
        ]);
    }
}