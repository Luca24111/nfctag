<?php

namespace App\Repository;

use App\Entity\NfcScan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @extends ServiceEntityRepository<NfcScan>
 *
 * @method NfcScan|null find($id, $lockMode = null, $lockVersion = null)
 * @method NfcScan[]    findAll()
 * @method NfcScan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method NfcScan|null findOneBy(array $criteria, array $orderBy = null)
 */
class NfcScanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NfcScan::class);
    }

    /**
     * Trova scansioni da sincronizzare per un dispositivo specifico
     */
    public function findScansToSync(string $deviceId, ?string $lastSync = null): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
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

        return $qb->getQuery()->getResult();
    }

    /**
     * Trova dispositivi attivi nelle ultime 24 ore
     */
    public function findActiveDevices(int $hours = 24): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('DISTINCT s.deviceId, s.deviceInfo, s.timestamp, s.userId')
           ->where('s.timestamp > :timeLimit')
           ->setParameter('timeLimit', new \DateTime("-{$hours} hours"))
           ->orderBy('s.timestamp', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trova statistiche di sincronizzazione per un utente
     */
    public function findSyncStatsForUser(int $userId): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s.syncStatus, COUNT(s.id) as count')
           ->where('s.userId = :userId')
           ->setParameter('userId', $userId)
           ->groupBy('s.syncStatus');

        $results = $qb->getQuery()->getResult();
        
        $stats = [
            'total' => 0,
            'synced' => 0,
            'received' => 0,
            'pending' => 0,
            'error' => 0
        ];

        foreach ($results as $result) {
            $status = $result['syncStatus'];
            $count = $result['count'];
            $stats[$status] = $count;
            $stats['total'] += $count;
        }

        return $stats;
    }

    /**
     * Trova scansioni recenti per un dispositivo
     */
    public function findRecentScansForDevice(string $deviceId, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
           ->where('s.deviceId = :deviceId')
           ->setParameter('deviceId', $deviceId)
           ->orderBy('s.timestamp', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trova scansioni duplicate (stesso NFC ID in breve tempo)
     */
    public function findDuplicateScans(int $timeWindowMinutes = 5): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('nfc_id', 'nfcId');
        $rsm->addScalarResult('device_id', 'deviceId');
        $rsm->addScalarResult('count', 'count');
        $rsm->addScalarResult('first_scan', 'firstScan');
        $rsm->addScalarResult('last_scan', 'lastScan');

        $sql = "
            SELECT 
                nfc_id,
                device_id,
                COUNT(*) as count,
                MIN(timestamp) as first_scan,
                MAX(timestamp) as last_scan
            FROM nfc_scans 
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL :timeWindow MINUTE)
            GROUP BY nfc_id, device_id
            HAVING COUNT(*) > 1
            ORDER BY count DESC
        ";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('timeWindow', $timeWindowMinutes);

        return $query->getResult();
    }

    /**
     * Pulisce scansioni vecchie (più di 30 giorni)
     */
    public function cleanupOldScans(int $days = 30): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->delete()
           ->where('s.timestamp < :cutoff')
           ->setParameter('cutoff', new \DateTime("-{$days} days"));

        return $qb->getQuery()->execute();
    }

    /**
     * Trova scansioni con errori di sincronizzazione
     */
    public function findSyncErrors(int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
           ->where('s.syncStatus = :status')
           ->setParameter('status', 'error')
           ->orderBy('s.timestamp', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Aggiorna lo stato di sincronizzazione per multiple scansioni
     */
    public function updateSyncStatus(array $scanIds, string $status): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->update()
           ->set('s.syncStatus', ':status')
           ->set('s.updatedAt', ':now')
           ->where('s.id IN (:ids)')
           ->setParameter('status', $status)
           ->setParameter('now', new \DateTimeImmutable())
           ->setParameter('ids', $scanIds);

        return $qb->getQuery()->execute();
    }

    /**
     * Trova dispositivi con problemi di sincronizzazione
     */
    public function findDevicesWithSyncIssues(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('device_id', 'deviceId');
        $rsm->addScalarResult('error_count', 'errorCount');
        $rsm->addScalarResult('last_error', 'lastError');
        $rsm->addScalarResult('total_scans', 'totalScans');

        $sql = "
            SELECT 
                device_id,
                COUNT(CASE WHEN sync_status = 'error' THEN 1 END) as error_count,
                MAX(CASE WHEN sync_status = 'error' THEN timestamp END) as last_error,
                COUNT(*) as total_scans
            FROM nfc_scans 
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY device_id
            HAVING error_count > 0
            ORDER BY error_count DESC
        ";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        return $query->getResult();
    }
}

