<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\Rate;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{

    #[Route('/currency/{id}', name: 'currency')]
    public function single(int $id): Response
    {
        $currency = $this->getDoctrine()
            ->getRepository(Currency::class)->find($id);
        return $this->render('currency.html.twig', ['currency' => $currency]);
    }

    #[Route('/{page}/{date}', name: 'currencies')]
    public function all(int $page, string $date): Response
    {
        if (!$date) {
            $date = $_POST['date'] ?? date('Y-m-d');
        } elseif(isset($_POST['date']) && $date !== $_POST['date']){
            $date = $_POST['date'];
        }

        $limit = 5;
        $rates = $this->getDoctrine()
            ->getRepository(Rate::class)->findByDate($date);

        $paginator = new Paginator($rates);
        $pages = ceil(count($paginator) / $limit);
        $paginator->getQuery()->setMaxResults($limit)->setFirstResult($limit * ($page - 1));
        return $this->render('currencies.html.twig', ['rates' => $paginator, 'date' => $date, 'pages' => $pages]);
    }
}
