<?php

namespace App\Controller;

use App\Entity\FichierNomBilan;
use App\Form\FichierNomBilanType;
use App\Repository\FichierBilanRepository;
use App\Repository\FichierNomBilanRepository;
use App\Repository\FichierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/fichier/nom/bilan')]
class FichierNomBilanController extends AbstractController
{
    #[Route('/', name: 'app_fichier_nom_bilan_index', methods: ['GET'])]
    public function index(FichierNomBilanRepository $fichierNomBilanRepository): Response
    {
        $user= $this->getUser();
        return $this->render('fichier_nom_bilan/index.html.twig', [
            'fichier_nom_bilans' => $fichierNomBilanRepository->findAll(),
            'user'=>$user->getUserIdentifier()
        ]);
    }




    #[Route('/admin/addBilan', name: 'app_fichierNomBilan_new', methods: ['GET'])]
    public function newBilan( FichierNomBilanRepository $fichierNomBilanRepository,EntityManagerInterface $entityManager): Response
    {
        // jappele la fonction pour ajouter un fichier avec le name en get
        $bilan = $fichierNomBilanRepository->insertFichier($entityManager,$_GET['bilan']);
        return $this->redirectToRoute('app_fichier_nom_bilan_index', [], Response::HTTP_SEE_OTHER);
    }



    #[IsGranted('ROLE_ADMIN', statusCode: 404, message: 'Access Denied.')]
    #[Route('/{id}/edit', name: 'app_fichier_nom_bilan_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FichierNomBilan $fichierNomBilan, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(FichierNomBilanType::class, $fichierNomBilan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fichier_nom_bilan_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fichier_nom_bilan/edit.html.twig', [
            'fichier_nom_bilan' => $fichierNomBilan,
            'form' => $form,
            'user'=>$user,
        ]);
    }

}
