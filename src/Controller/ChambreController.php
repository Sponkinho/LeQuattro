<?php

namespace App\Controller;

use DateTime;

use App\Entity\Chambre;
use App\Entity\Category;
use App\Form\ChambreFormType;
use App\Repository\ChambreRepository;
use App\Repository\CategoryRepository;
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
    public function showChambres(EntityManagerInterface $entityManager): Response
    {
        return $this->render('chambre/show_chambres.html.twig');
    } // end of showChambre() -> POUR AFFICHER TOUTES LES CHAMBRES

    #[Route('/voir-chambres/{category}', name: 'show_chambres_from_category', methods: ['GET'])]
    public function showChambresFromCategory(string $category, ChambreRepository $chambreRepository): Response
    {
        $chambresFromCategory = $chambreRepository->findBy(['deletedAt' => null, 'category' => $category]);

        return  $this->render('chambre/show_chambres_from_category.html.twig', [
            'chambres' => $chambresFromCategory,
            'category' => $category
        ]);
    }

    #[Route('/voir-une-chambre/{id}', name: 'show_chambre', methods: ['GET'])]
    public function showChambre(Chambre $chambre, EntityManagerInterface $entityManager): Response
    {

        $chambres = $entityManager->getRepository(Chambre::class)->findBy(['deletedAt' => null]);

        return $this->render('chambre/show_chambre_solo.html.twig', [
            'chambre' => $chambre,
            'chambres' => $chambres,
        ]);
    } // POUR AFFICHER UNE  CHAMBRE INDIVIDUELEMENT 



    // start function create() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!   
    #[Route('/admin/ajouter-une-chambre', name: 'create_chambre', methods: ['GET', 'POST'])]
    public function createChambre(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
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

            $this->addFlash('success', 'La chambre ajout??e avec succ??s !');
            return $this->redirectToRoute('show_dashboard');
        } // end if $form

        return $this->render('chambre/create_chambre.html.twig', [
            'form' => $form->createView()
        ]);
    } // end function create() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!

    // start function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!    
    #[Route('/modifier-une-chambre/{id}', name: 'update_chambre', methods: ['GET', 'POST'])]
    public function updateChambre(Chambre $chambre, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        # R??cup??ration de la photo actuelle
        $originalPhoto = $chambre->getPhoto();

        $form = $this->createForm(ChambreFormType::class, $chambre, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $chambre->setUpdatedAt(new DateTime());
            $photo = $form->get('photo')->getData();

            if ($photo) {
                // M??thode cr????e par nous-m??me pour r??utiliser du code qu'on r??p??te (create() et update())
                $this->handleFile($chambre, $photo, $slugger);
            } else {
                $chambre->setPhoto($originalPhoto);
            } // end if $photo

            $entityManager->persist($chambre);
            $entityManager->flush();

            $this->addFlash('success', 'La modification est r??ussie avec succ??s !');
            return $this->redirectToRoute('show_backoffice_chambre');
        } // end if $form

        return $this->render('chambre/create_chambre.html.twig', [
            'form' => $form->createView(),
            'chambre' => $chambre
        ]);
    } // end function update()// end function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!



    // DEBUT FONCTION SUPPRIMER CHAMBRES
    #[Route('/archiver-un-chambre/{id}', name: 'soft_delete_chambre', methods: ['GET'])]
    public function softDeleteChambre(Chambre $chambre, EntityManagerInterface $entityManager): RedirectResponse
    {
        $chambre->setDeletedAt(new DateTime());

        $entityManager->persist($chambre);
        $entityManager->flush();

        $this->addFlash('success', 'La chambre a bien ??t?? archiv?? !');
        return $this->redirectToRoute('show_backoffice_chambre');
    }  // end function update() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!

    // start function showBackofficeChambre() CETTE METHODE(fonction) EST A METTRE DANS ADMIN CONTROLLER !!!
    #[Route('/voir-backoffice-chambre', name: 'show_backoffice_chambre', methods: ['GET'])]
    public function showBackofficeChambre(EntityManagerInterface $entityManager): Response
    {
        $chambres = $entityManager->getRepository(Chambre::class)->findBy(['deletedAt' => null]);

        return $this->render('admin/back_office_chambre.html.twig', [
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
            $this->addFlash('warning', 'La photo de la chambre ne s\'est pas import??e avec succ??s. Veuillez r??essayer.');
            //            return $this->redirectToRoute('create_chambre');
        }
    } // end class handleFile()


}
