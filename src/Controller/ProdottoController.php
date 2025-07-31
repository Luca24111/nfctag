<?php
// src/Controller/ProdottoController.php
namespace App\Controller;

use App\Entity\Prodotto;
use App\Form\ProdottoType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/prodotto')]
class ProdottoController extends AbstractController
{
    #[Route('/nuovo', name: 'prodotto_nuovo')]
    public function nuovo(Request $request, SluggerInterface $slugger): Response
    {
        $prodotto = new Prodotto();
        $form = $this->createForm(ProdottoType::class, $prodotto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immagineFile = $form->has('immagine') ? $form->get('immagine')->getData() : null;
            $immaginePath = null;

            if ($immagineFile) {
                $originalFilename = pathinfo($immagineFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $immagineFile->guessExtension();

                try {
                    $immagineFile->move(
                        $this->getParameter('prodotti_directory'),
                        $newFilename
                    );
                    $immaginePath = 'img/prodotti/' . $newFilename;
                    $prodotto->setImage($immaginePath);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Errore nel caricamento immagine.');
                }
            }

            $entityManager = $this->getDoctrine()->getManager(); // Deprecated in Symfony 5.4+, usare preferibilmente injection diretta se possibile
            $entityManager->persist($prodotto);
            $entityManager->flush();

            return $this->render('magazzino/prodotto_successo.html.twig', [
                'immagine' => $immaginePath,
                'nome' => $prodotto->getName(),
                'descrizione' => $prodotto->getDescription(),
                'posizione' => $prodotto->getScaffale(),
            ]);
        }

        return $this->render('prodotto/nuovo.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/modifica/{id}', name: 'app_prodotto_modifica')]
    public function modifica(Request $request, Prodotto $prodotto, SluggerInterface $slugger, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProdottoType::class, $prodotto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immagineFile = $form->has('immagine') ? $form->get('immagine')->getData() : null;

            if ($immagineFile) {
                $originalFilename = pathinfo($immagineFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $immagineFile->guessExtension();

                try {
                    $immagineFile->move(
                        $this->getParameter('prodotti_directory'),
                        $newFilename
                    );
                    $immaginePath = 'img/prodotti/' . $newFilename;
                    $prodotto->setImage($immaginePath);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Errore nel caricamento immagine.');
                }
            }

            $prodotto->setUpdateDate(new \DateTime());

            $entityManager->flush();

            return $this->redirectToRoute('app_magazzino');
        }

        return $this->render('magazzino/edit.html.twig', [
            'form' => $form->createView(),
            'prodotto' => $prodotto,
        ]);
    }
}