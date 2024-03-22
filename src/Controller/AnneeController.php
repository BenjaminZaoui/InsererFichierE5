<?php

namespace App\Controller;

use App\Entity\Annee;
use App\Form\AnneeType;
use App\Repository\AnneeRepository;
use App\Repository\FichierNomBilanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/annee')]
class AnneeController extends AbstractController
{
    #[Route('/admin/addAnnee', name: 'app_AnneeBilan_new', methods: ['GET'])]
    public function newAnnee( AnneeRepository $anneeRepository,EntityManagerInterface $entityManager): Response
    {
        // jappele la fonction pour ajouter un fichier avec le name en get
        if (!$anneeRepository->findOneBy(['annee_bilan'=>$_GET['annee']]))
        {
            $annee = $anneeRepository->insertAnnee($entityManager, $_GET['annee']);
        }
        return $this->redirectToRoute('app_info_client_new', [], Response::HTTP_SEE_OTHER);
    }
}
