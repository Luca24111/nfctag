<?php

namespace App\Controller;

use App\Entity\Prodotto;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NfcController extends AbstractController
{
    #[Route('/nfc', name: 'app_nfc')]
    public function index()
    {
        return $this->render('nfc/index.html.twig');
    }

    #[Route('/nfc/read', name: 'nfc_read', methods: ['POST'])]
    public function readNfc(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        try {
            $nfcId = $request->request->get('nfcId');
    
            if (!$nfcId) {
                return new JsonResponse(['error' => 'NFC id is missing'], 400);
            }
    
            $em = $doctrine->getManager();
            $product = $em->getRepository(Prodotto::class)->find($nfcId);
    
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], 404);
            }
    
            // Inverti il campo "out"
            $currentOut = $product->isOut();
            $product->setOut(!$product->isOut());
            $product->setUpdateDate(new \DateTime());
    
            $em->persist($product);
            $em->flush();
    
            $data = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'avaiable' => $product->isAvaiable(),
                'out' => $product->isOut(),
                'created_at' => $product->getCreatedAt()?->format('Y-m-d H:i'),
                'updated_at' => $product->getUpdateDate()?->format('Y-m-d H:i'),
            ];
    
            return new JsonResponse(['product' => $data]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Errore interno: ' . $e->getMessage()
            ], 500);
        }
    }
    
}
