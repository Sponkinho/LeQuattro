<?php

namespace App\Controller;

use DateTime;

use App\Entity\Chambre;
use App\Form\ChambreFormType;
use App\Repository\ChambreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class ChambreController extends AbstractController
{
     #[Route('/voir-chambres', name: 'show_chambres', methods: ['GET'])]   
     public function showChambres( EntityManagerInterface $entityManager): Response
        {
        $chambres = $entityManager->getRepository(Chambre::class)->findBy(['deletedAt' => null]);

        return $this->render('chambre/show_chambres.html.twig', [
            'chambres' => $chambres,

        ]);

        } // end of showChambre() -> POUR AFFICHER LES CHAMBRES
        
        #[Route('/ajouter-une-chambre', name: 'create_chambre', methods: ['GET', 'POST'])]
        public function createChambre( Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
        {
            // $chambre = $entityManager->getRepository(Chambre::class)->findBy(['deletedAt' => null]);
            $chambre = new Chambre();
            
    
            $form = $this->createForm(ChambreFormType::class, $chambre)
                ->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
    
                $chambre->setCreatedAt(new DateTime);
                $chambre->setUpdatedAt(new DateTime);
    

                $photo = $form->get('photo')->getData();
    
                if ($photo) {
                  
                    $this->handleFile($chambre, $photo, $slugger);
                } // end if $photo
    
                $entityManager->persist($chambre);
                $entityManager->flush();
    
                $this->addFlash('success', 'La chambre ajoutée avec succès !');
                return $this->redirectToRoute('show_chambres');
            } // end if $form
    
            return $this->render('chambre/create_chambre.html.twig', [
                'form' => $form->createView()
            ]);
        } // end function create()

    // start function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!    
    #[Route('/modifier-une-chambre', name: 'update_chambre', methods: ['GET', 'POST'])]
    public function updateChambre(Chambre $chambre, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): RedirectResponse
    {
        # Récupération de la photo actuelle
        $originalPhoto = $chambre->getPhoto();

        $form = $this->createForm(ChambreFormType::class, $chambre, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $chambre->setUpdatedAt(new DateTime());
            $photo = $form->get('photo')->getData();

            if ($photo) {
                // Méthode créée par nous-même pour réutiliser du code qu'on répète (create() et update())
                $this->handleFile($chambre, $photo, $slugger);
            } else {
                $chambre->setPhoto($originalPhoto);
            } // end if $photo

            $entityManager->persist($chambre);
            $entityManager->flush();

            $this->addFlash('success', 'La modification est réussie avec succès !');
            return $this->redirectToRoute('show_chambres');
        } // end if $form

        return $this->render('chambre/create_chambre.html.twig', [
            'form' => $form->createView(),
            'chambre' => $chambre
        ]);
    } // end function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!

     

    // DEBUT FONCTION SUPPRIMER CHAMBRES
    #[Route('/archiver-un-chambre/{id}', name: 'soft_delete_chambre', methods: ['GET'])]
    public function softDeleteChambre(Chambre $chambre, EntityManagerInterface $entityManager): RedirectResponse
    {
        $chambre->setDeletedAt(new DateTime());

        $entityManager->persist($chambre);
        $entityManager->flush();

        $this->addFlash('success', 'La chambre a bien été archivé !');
        return $this->redirectToRoute('show_backoffice_chambre');

    }  // end function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!
        

        // start function showBackofficeChambre() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!
        #[Route('/voir-backoffice-chambre', name: 'show_backoffice_chambre', methods: ['GET'])]
        public function showBackofficeChambre(EntityManagerInterface $entityManager): Response
        {   
            $chambres = $entityManager->getRepository(Chambre::class)->findBy(['deletedAt' => null]);

            return $this->render('chambre/back_office_chambre.html.twig', [
                'chambres' => $chambres,
            ]);
        } // end function showBackofficeChambre() 



////////////////////////////////////////////////////////  FONCTIONS CREES PAR NOUS MEME /////////////////////////////////////////////////////////////


        private function handleFile(Chambre $chambre, UploadedFile $photo, SluggerInterface $slugger): void
        {

            $extension = '.' . $photo->guessExtension();
    

            $safeFilename = $slugger->slug($photo->getClientOriginalName());

            $newFilename = $safeFilename . '_' . uniqid() . $extension;
    
  
            try {

                $photo->move($this->getParameter('uploads_dir'), $newFilename);

                $chambre->setPhoto($newFilename);
            } catch (FileException $exception) {
                $this->addFlash('warning', 'La photo de la chambre ne s\'est pas importée avec succès. Veuillez réessayer.');
                //            return $this->redirectToRoute('create_chambre');
            }
        } // end class handleFile()
    
}