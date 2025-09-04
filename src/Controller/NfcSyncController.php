<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\NfcScan;
use App\Entity\User;

final class NfcSyncController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/nfc/sync', name: 'nfc_sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Dati non validi'], 400);
            }

            // Genera ID dispositivo se non esiste
            $deviceId = $data['device_id'] ?? $this->generateDeviceId();
            
            // Salva la scansione nel database
            $scan = new NfcScan();
            $scan->setDeviceId($deviceId);
            $scan->setNfcId($data['nfc_id'] ?? '');
            $scan->setProductName($data['product_name'] ?? '');
            $scan->setProductStatus($data['product_status'] ?? '');
            $scan->setScanType($data['scan_type'] ?? 'read');
            $scan->setTimestamp(new \DateTime());
            $scan->setUserId($this->getUser()?->getId());
            $scan->setDeviceInfo($data['device_info'] ?? '');
            $scan->setLocation($data['location'] ?? '');
            $scan->setSyncStatus('synced');

            $this->entityManager->persist($scan);
            $this->entityManager->flush();

            // Pubblica evento per altri dispositivi (qui useremo una soluzione semplice)
            $this->publishSyncEvent($scan);

            return $this->json([
                'status' => 'success',
                'message' => 'Scansione sincronizzata',
                'scan_id' => $scan->getId(),
                'device_id' => $deviceId,
                'timestamp' => $scan->getTimestamp()->format('c')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Errore durante la sincronizzazione',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/nfc/sync/status', name: 'nfc_sync_status', methods: ['GET'])]
    public function getSyncStatus(Request $request): JsonResponse
    {
        $deviceId = $request->query->get('device_id');
        $lastSync = $request->query->get('last_sync');

        if (!$deviceId) {
            return $this->json(['error' => 'Device ID richiesto'], 400);
        }

        // Trova scansioni più recenti dell'ultima sincronizzazione
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s')
           ->from(NfcScan::class, 's')
           ->where('s.deviceId != :deviceId')
           ->andWhere('s.syncStatus = :syncStatus')
           ->setParameter('deviceId', $deviceId)
           ->setParameter('syncStatus', 'synced')
           ->orderBy('s.timestamp', 'DESC')
           ->setMaxResults(100);

        if ($lastSync) {
            $qb->andWhere('s.timestamp > :lastSync')
               ->setParameter('lastSync', new \DateTime($lastSync));
        }

        $scans = $qb->getQuery()->getResult();

        $scanData = array_map(function($scan) {
            return [
                'id' => $scan->getId(),
                'nfc_id' => $scan->getNfcId(),
                'product_name' => $scan->getProductName(),
                'product_status' => $scan->getProductStatus(),
                'scan_type' => $scan->getScanType(),
                'timestamp' => $scan->getTimestamp()->format('c'),
                'device_id' => $scan->getDeviceId(),
                'device_info' => $scan->getDeviceInfo(),
                'location' => $scan->getLocation(),
                'user_id' => $scan->getUserId()
            ];
        }, $scans);

        return $this->json([
            'status' => 'success',
            'scans' => $scanData,
            'count' => count($scanData),
            'last_sync' => (new \DateTime())->format('c')
        ]);
    }

    #[Route('/api/nfc/sync/devices', name: 'nfc_sync_devices', methods: ['GET'])]
    public function getActiveDevices(): JsonResponse
    {
        // Trova dispositivi attivi nelle ultime 24 ore
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT s.deviceId, s.deviceInfo, s.timestamp, s.userId')
           ->from(NfcScan::class, 's')
           ->where('s.timestamp > :yesterday')
           ->setParameter('yesterday', new \DateTime('-24 hours'))
           ->orderBy('s.timestamp', 'DESC');

        $devices = $qb->getQuery()->getResult();

        $deviceData = array_map(function($device) {
            return [
                'device_id' => $device['deviceId'],
                'device_info' => $device['deviceInfo'],
                'last_activity' => $device['timestamp']->format('c'),
                'user_id' => $device['userId']
            ];
        }, $devices);

        return $this->json([
            'status' => 'success',
            'devices' => $deviceData,
            'count' => count($deviceData)
        ]);
    }

    #[Route('/api/nfc/sync/ack', name: 'nfc_sync_ack', methods: ['POST'])]
    public function acknowledgeSync(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $scanIds = $data['scan_ids'] ?? [];

        if (empty($scanIds)) {
            return $this->json(['error' => 'Nessun ID scansione fornito'], 400);
        }

        // Marca le scansioni come ricevute
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(NfcScan::class, 's')
           ->set('s.syncStatus', ':status')
           ->where('s.id IN (:ids)')
           ->setParameter('status', 'received')
           ->setParameter('ids', $scanIds);

        $updated = $qb->getQuery()->execute();

        return $this->json([
            'status' => 'success',
            'message' => "{$updated} scansioni marcate come ricevute"
        ]);
    }

    private function generateDeviceId(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    private function publishSyncEvent(NfcScan $scan): void
    {
        // Per ora usiamo una soluzione semplice
        // In futuro potremmo integrare Mercure o WebSocket
        // Per ora salviamo l'evento nel database per la sincronizzazione
    }
}

