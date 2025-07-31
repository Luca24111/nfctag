<?php

namespace App\Controller;

use App\Repository\ProdottoRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AIController extends AbstractController
{
    #[Route('/ai/where-product', name: 'ai_where_product', methods: ['POST'])]
    public function whereProduct(Request $request, ProdottoRepository $prodottoRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = strtolower(trim($data['question'] ?? ''));

        if (empty($question)) {
            return $this->json([
                'answer' => "Inserisci il nome di un prodotto da cercare."
            ]);
        }

        // Ricerca avanzata con QueryBuilder
        $prodotti = $prodottoRepository->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $question . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        if (count($prodotti) > 0) {
            $answer = '<div style="margin-bottom: 1rem;"><strong>Risultati trovati per "' . htmlspecialchars($question) . '":</strong></div>';
            
            foreach ($prodotti as $prodotto) {
                $status = $prodotto->isOut() ? "Fuori" : "In magazzino";
                $statusClass = $prodotto->isOut() ? "out" : "in";
                $statusColor = $prodotto->isOut() ? "#ff3b30" : "#34c759";
                
                $answer .= '<div style="background: rgba(44, 44, 46, 0.6); border-radius: 12px; padding: 1rem; margin-bottom: 0.8rem; border: 1px solid rgba(255, 255, 255, 0.05);">';
                $answer .= '<div style="font-weight: 600; color: white; margin-bottom: 0.5rem; font-size: 1rem;">' . htmlspecialchars($prodotto->getName()) . '</div>';
                $answer .= '<div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">';
                $answer .= '<span style="color: #999;">Scaffale: <strong>' . htmlspecialchars($prodotto->getScaffale() ?? 'N/A') . '</strong></span>';
                $answer .= '<span style="background: rgba(' . ($prodotto->isOut() ? '255, 59, 48' : '52, 199, 89') . ', 0.2); color: ' . $statusColor . '; border: 1px solid rgba(' . ($prodotto->isOut() ? '255, 59, 48' : '52, 199, 89') . ', 0.3); padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">' . $status . '</span>';
                $answer .= '</div>';
                $answer .= '</div>';
            }
            
            return $this->json(['answer' => $answer]);
        }

        // Se non trova corrispondenze dirette, cerca prodotti simili
        $allProducts = $prodottoRepository->findAll();
        $similarProducts = [];
        
        foreach ($allProducts as $prodotto) {
            $nome = strtolower($prodotto->getName());
            $distance = levenshtein($question, $nome);
            
            // Se la distanza è ragionevole (max 3 caratteri di differenza)
            if ($distance <= 3 && $distance > 0) {
                $similarProducts[] = [
                    'prodotto' => $prodotto,
                    'distance' => $distance
                ];
            }
        }
        
        // Ordina per similarità
        usort($similarProducts, function($a, $b) {
            return $a['distance'] - $b['distance'];
        });
        
        if (count($similarProducts) > 0) {
            $answer = '<div style="margin-bottom: 1rem;"><strong>Non ho trovato corrispondenze esatte, ma forse cercavi:</strong></div>';
            
            foreach (array_slice($similarProducts, 0, 3) as $similar) {
                $prodotto = $similar['prodotto'];
                $status = $prodotto->isOut() ? "Fuori" : "In magazzino";
                $statusColor = $prodotto->isOut() ? "#ff3b30" : "#34c759";
                
                $answer .= '<div style="background: rgba(44, 44, 46, 0.6); border-radius: 12px; padding: 1rem; margin-bottom: 0.8rem; border: 1px solid rgba(255, 255, 255, 0.05);">';
                $answer .= '<div style="font-weight: 600; color: white; margin-bottom: 0.5rem; font-size: 1rem;">' . htmlspecialchars($prodotto->getName()) . '</div>';
                $answer .= '<div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">';
                $answer .= '<span style="color: #999;">Scaffale: <strong>' . htmlspecialchars($prodotto->getScaffale() ?? 'N/A') . '</strong></span>';
                $answer .= '<span style="background: rgba(' . ($prodotto->isOut() ? '255, 59, 48' : '52, 199, 89') . ', 0.2); color: ' . $statusColor . '; border: 1px solid rgba(' . ($prodotto->isOut() ? '255, 59, 48' : '52, 199, 89') . ', 0.3); padding: 0.3rem 0.6rem; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">' . $status . '</span>';
                $answer .= '</div>';
                $answer .= '</div>';
            }
            
            return $this->json(['answer' => $answer]);
        }

        return $this->json([
            'answer' => '<div style="text-align: center; padding: 2rem; color: #999;"><strong>Nessun prodotto trovato</strong><br>per "' . htmlspecialchars($question) . '"<br><br>Prova con un nome diverso o più generico.</div>'
        ]);
    }
} 