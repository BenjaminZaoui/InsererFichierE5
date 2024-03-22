<?php

namespace App\Controller;

use App\Entity\Annee;
use App\Entity\Fichier;
use App\Entity\FichierBilan;
use App\Entity\FichierDemande;
use App\Entity\FichierNomBilan;
use App\Entity\InfoClient;
use App\Form\AddFichierBilanType;
use App\Form\AddFichierDemandeType;
use App\Form\FichierBilanType;
use App\Form\FichierDemandeType;
use App\Repository\FichierBilanRepository;
use App\Repository\FichierDemandeRepository;
use App\Repository\FichierNomBilanRepository;
use App\Repository\InfoClientRepository;
use App\Repository\FichierRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\PseudoTypes\IntegerValue;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/fichier/demande')]
class FichierDemandeController extends AbstractController
{


    #[Route('/liste', name: 'app_fichier_demande_index', methods: ['GET'])]
    public function index(InfoClientRepository $infoClientRepository, FichierDemandeRepository $fichierDemandeRepository, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();

        $client = $infoClientRepository->findAllClient();
        $fichier = $entityManager->getRepository(Fichier::class)->findAll();
//        dd($fichierDemandeRepository->findAll());
        return $this->render('fichier_demande/index.html.twig', [
            'fichiers_demandes' => $fichierDemandeRepository->findBy(['status' => 1]),
            'clients' => $client,
            'fichiers' => $fichier,
            'user' => $user->getUserIdentifier(),
        ]);

    }
    #[IsGranted('ROLE_ADMIN', statusCode: 404, message: 'Access Denied.')]
    #[Route('/listeSupprimer', name: 'app_fichier_demande_supprimer', methods: ['GET'])]
    public function index2(InfoClientRepository $infoClientRepository, FichierDemandeRepository $fichierDemandeRepository, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();

        return $this->render('info_client/index_fichiers_supprimer.html.twig', [
            //méthode créer par moi même dans infoClientRepository pour virer le compte par defaut
            'info_clients' => $infoClientRepository->findAllClient(),
            'user'=>$user->getUserIdentifier()
        ]);

    }


    #[Route('/mesFichiers/{id}', name: 'mesFichiers', methods: ['GET', 'POST'])]
    public function indexFichier($id,FichierRepository $fichierRepository,FichierDemandeRepository $fichierDemandeRepository, FichierBilanRepository $fichierBilanRepository, FichierNomBilanRepository $fichierNomBilanRepository, Request $request, EntityManagerInterface $entityManager, InfoClientRepository $infoClientRepository): Response
    {
        //récupère une array d'objet fichier qui n'éxiste pas pour ce client
        $fichierSansClient = $fichierRepository->findFichiersSansDemandeClient($id);
        //pareil pour les fichiersNomBilans
        $fichierNomBilanSansClient= $fichierNomBilanRepository->findFichiersBilansSansDemandeClient($id);



        $client = $infoClientRepository->find($id);
        $societeClient = $client->getNomSociete();
        $user = $this->getUser();
        $annee = $entityManager->getRepository(Annee::class)->findAll();
        $bilan = $entityManager->getRepository(FichierNomBilan::class)->findAll();
        $fichierBilan = new FichierBilan();
        $formBilan = $this->createForm(AddFichierBilanType::class, $fichierBilan,['fichiers'=>$fichierNomBilanSansClient]);
        $formBilan->handleRequest($request);
        $fichierDemande = new FichierDemande();
        $form = $this->createForm(AddFichierDemandeType::class, $fichierDemande,['fichiers'=>$fichierSansClient]);
        $form->handleRequest($request);




            $fichiers = $entityManager->getRepository(FichierDemande::class)->findBy([
                'id_info_client' => $client,
                'status' => 1
            ]);

            $fichiersBilans = $entityManager->getRepository(FichierBilan::class)->findBy([
                'id_info_client' => $client,
                'status' => 1
            ]);


        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('nom_fichier_demande')->getData();
            $idNomFichier = $form->get('id_fichier')->getData()->getId();
            $idNomFichier = $fichierRepository->find($idNomFichier);
            $nomOriginal = $form->get('nom_fichier_demande')->getData()->getClientOriginalName();
            // le chemin ou le fichier est inserer
            $nomOriginal = $uploadedFile->getClientOriginalName();
            $destinationDirectory = 'C:\Users\benja\projects\php\InsererFichier\public\fichier';//D:\XAMPP\htdocs\WEB\InsererFichier\public\fichier
            $newFilename = $nomOriginal;
            $uploadedFile->move($destinationDirectory, $newFilename);
            $fichierDemande->setIdUser($user);
            $fichierDemande->setNomFichierDemande($newFilename);
            $fichierDemande->setIdInfoClient($client);
            $fichierDemande->setIdFichier($idNomFichier);
            $fichierDemande->setStatus(1);
            $entityManager->persist($fichierDemande);
            $entityManager->flush();
            $fichierDemandeRepository->save($fichierDemande, true);
            return $this->redirectToRoute('mesFichiers', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        if ($formBilan->isSubmitted() && $formBilan->isValid()) {
            $uploadedFile = $formBilan->get('nom_fichier_bilan')->getData();
            $idNomFichierBilan = $formBilan->get('id_fichier_bilan')->getData()->getId();
            $idNomFichierBilan = $fichierNomBilanRepository->find($idNomFichierBilan);
            $nomOriginal = $formBilan->get('nom_fichier_bilan')->getData()->getClientOriginalName();
            $nomOriginal = $uploadedFile->getClientOriginalName();
            //Changer le chemin d'accès par le votre
            $destinationDirectory = 'C:/Users/benja/projects/php/InsererFichier/public/fichier/';
            $newFilename = $nomOriginal;
            $uploadedFile->move($destinationDirectory, $newFilename);
            $fichierBilan->setIdUser($user);
            $fichierBilan->setNomFichierBilan($newFilename);
            $fichierBilan->setIdInfoClient($client);
            $fichierBilan->setIdFichierBilan($idNomFichierBilan);
            $fichierBilan->setStatus(1);
            $entityManager->persist($fichierBilan);
            $entityManager->flush();
            $fichierBilanRepository->save($fichierBilan, true);
            return $this->redirectToRoute('mesFichiers', ['id' => $id], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fichier_demande/unFichier.html.twig', [
            'fichier_demandes_sansClient'=>$fichierSansClient,
            'fichier_bilan_sansClient'=>$fichierNomBilanSansClient,
            'fichier_demandes' => $fichiers,
            'fichier_bilans' => $fichiersBilans,
            'user' => $user->getUserIdentifier(),
            'idClient' => $id,
            'clients' => $client,
            'societe' => $societeClient,
            'bilans' => $bilan,
            'annees' => $annee,
            'formBilan' => $formBilan,
            'form'=>$form,
        ]);
    }
    #[IsGranted('ROLE_ADMIN', statusCode: 404, message: 'Access Denied.')]
    #[Route('/mesFichiersSupprimer/{id}', name: 'mesFichiersSupprimer', methods: ['GET', 'POST'])]
    public function indexFichierSupprimer($id, FichierBilanRepository $fichierBilanRepository, FichierNomBilanRepository $fichierNomBilanRepository, Request $request, EntityManagerInterface $entityManager, InfoClientRepository $infoClientRepository): Response
    {
        $user = $this->getUser();
        if ($user->getRoles()[0] == "ROLE_ADMIN") {
            $client = $infoClientRepository->find($id);
            $societeClient = $client->getNomSociete();

            $annee = $entityManager->getRepository(Annee::class)->findAll();
            $bilan = $entityManager->getRepository(FichierNomBilan::class)->findAll();
            $fichiers = $entityManager->getRepository(FichierDemande::class)->findBy([
                'id_info_client' => $client,
                'status'=>0
            ]);
            $fichiersBilans = $entityManager->getRepository(FichierBilan::class)->findBy([
                'id_info_client' => $client,
                'status'=>0
            ]);

            return $this->render('fichier_demande/unFichierSupprimer.html.twig', [
                'fichier_demandes' => $fichiers,
                'fichier_bilans' => $fichiersBilans,
                'user' => $user->getUserIdentifier(),
                'idClient' => $id,
                'clients' => $client,
                'societe' => $societeClient,
                'bilans' => $bilan,
                'annees' => $annee,
            ]);
        }
        return $this->redirectToRoute('app_info_client_index',[], Response::HTTP_SEE_OTHER);
    }


    #[Route('/new', name: 'app_fichier_demande_new', methods: ['GET', 'POST'])]
    public function new(EntityManagerInterface $entityManager, Request $request, InfoClientRepository $infoClientRepository, FichierRepository $fichierRepository, FichierDemandeRepository $fichierDemandeRepository): Response
    {
        $user = $this->getUser();
        $fichierDemande = new FichierDemande();
        $form = $this->createForm(FichierDemandeType::class, $fichierDemande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('nom_fichier_demande')->getData();
            // Il s'agit de l'id du client
            $idClient = $form->get('id_info_client')->getData()->getid();
            $idClient = $infoClientRepository->find($idClient);

            //Il s'agit de l'id du fichier demander pour tout les clients
            $idNomFichier = $form->get('id_fichier')->getData()->getId();
            $idNomFichier = $fichierRepository->find($idNomFichier);
            $nomOriginal = $form->get('nom_fichier_demande')->getData()->getClientOriginalName();
            // le chemin ou le fichier est inserer
            $nomOriginal = $uploadedFile->getClientOriginalName();
            $destinationDirectory = 'C:\Users\benja\projects\php\InsererFichier\public\fichier';//D:\XAMPP\htdocs\WEB\InsererFichier\public\fichier
            $newFilename = $nomOriginal;
            $uploadedFile->move($destinationDirectory, $newFilename);
            $fichierDemande->setIdUser($user);
            $fichierDemande->setNomFichierDemande($newFilename);
            $fichierDemande->setIdInfoClient($idClient);
            $fichierDemande->setIdFichier($idNomFichier);
            $fichierDemande->setStatus(1);
            $entityManager->persist($fichierDemande);
            $entityManager->flush();

            $fichierDemandeRepository->save($fichierDemande, true);
            return $this->redirectToRoute('app_fichier_demande_index', [], Response::HTTP_SEE_OTHER);

        }

        return $this->renderForm('fichier_demande/new.html.twig', [
            'nom_fichier_demande' => $fichierDemande,
            'form' => $form,
            'user' => $user->getUserIdentifier()
        ]);
    }


    #[Route('/edit/{id}', name: 'app_fichier_demande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FichierDemande $fichierDemande, FichierDemandeRepository $fichierDemandeRepository): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(FichierDemandeType::class, $fichierDemande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichierDemandeRepository->save($fichierDemande, true);
//            $entityManager->flush();

            return $this->redirectToRoute('app_fichier_demande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('fichier_demande/edit.html.twig', [
            'fichier_demande' => $fichierDemande,
            'form' => $form,
            'user' => $user->getUserIdentifier()
        ]);
    }


    #[Route('/view/{name}', name: 'app_view_pdf', methods: ['GET'])]
    public function view_pdf($name)
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        $filename = $name;

        $filePath = str_replace('\\', '/', $projectRoot) . '/public/fichier/' . $filename;

        return $this->file($filePath, null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/{id}', name: 'app_fichier_demande_delete', methods: ['POST'])]
    public function delete(Request $request, FichierDemande $fichierDemande, FichierDemandeRepository $fichierDemandeRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $fichierDemande->getId(), $request->request->get('_token'))) {
            $fichierDemandeRepository->save($fichierDemande->setStatus(0), true);
        }

        return $this->redirectToRoute('app_fichier_demande_index', [], Response::HTTP_SEE_OTHER);
    }


}
