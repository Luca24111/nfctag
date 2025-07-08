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

    // src/Controller/NfcController.php

    #[Route('/nfc/read', name: 'nfc_read', methods: ['POST'])]
    public function readNfc(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $nfcId = $request->request->get('nfcId');
        if (!$nfcId) {
            return new JsonResponse(['error' => 'NFC id is missing'], 400);
        }

        $em = $doctrine->getManager();
        $repo = $em->getRepository(Prodotto::class);
        $product = $repo->findOneBy(['nfcId' => (int)$nfcId]);

        if (!$product) {
            // redirect alla creazione con nfcId in query
            $createUrl = $this->generateUrl('app_prodotto_nuovo', [
                'nfcId' => $nfcId
            ]);
            return new JsonResponse([
                'error'      => 'Product not found',
                'redirectTo' => $createUrl
            ], 404);
        }

        // esiste già: inverto lo stato
        $product->setOut(!$product->isOut())
                ->setUpdateDate(new \DateTime());
        $em->flush();

        return new JsonResponse([
            'product' => [
                'id'          => $product->getId(),
                'nfcId'       => $product->getNfcId(),
                'name'        => $product->getName(),
                'out'         => $product->isOut(),
                'created_at'  => $product->getCreatedAt()?->format('Y-m-d H:i'),
                'updated_at'  => $product->getUpdateDate()?->format('Y-m-d H:i'),
            ]
        ]);
    }


}
