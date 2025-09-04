<?php

namespace App\Repository;

use App\Entity\Prodotto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prodotto>
 *
 * @method Prodotto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prodotto|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prodotto[]    findAll()
 * @method Prodotto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProdottoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prodotto::class);
    }

    /**
     * Trova prodotti con paginazione
     */
    public function findPaginated(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.created_at', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Applica filtri
        if (isset($filters['available'])) {
            $qb->andWhere('p.avaiable = :available')
               ->setParameter('available', $filters['available']);
        }

        if (isset($filters['out'])) {
            $qb->andWhere('p.out = :out')
               ->setParameter('out', $filters['out']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['scaffale']) && !empty($filters['scaffale'])) {
            $qb->andWhere('p.scaffale = :scaffale')
               ->setParameter('scaffale', $filters['scaffale']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Conta prodotti totali con filtri
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        // Applica stessi filtri
        if (isset($filters['available'])) {
            $qb->andWhere('p.avaiable = :available')
               ->setParameter('available', $filters['available']);
        }

        if (isset($filters['out'])) {
            $qb->andWhere('p.out = :out')
               ->setParameter('out', $filters['out']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['scaffale']) && !empty($filters['scaffale'])) {
            $qb->andWhere('p.scaffale = :scaffale')
               ->setParameter('scaffale', $filters['scaffale']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trova prodotti in magazzino (available = true)
     */
    public function findInMagazzino(int $page = 1, int $limit = 20): array
    {
        return $this->findPaginated($page, $limit, ['available' => true]);
    }

    /**
     * Trova prodotti fuori magazzino (out = true)
     */
    public function findOutMagazzino(int $page = 1, int $limit = 20): array
    {
        return $this->findPaginated($page, $limit, ['out' => true]);
    }

    /**
     * Cerca prodotti per nome o descrizione
     */
    public function searchProdotti(string $search, int $page = 1, int $limit = 20): array
    {
        return $this->findPaginated($page, $limit, ['search' => $search]);
    }

    /**
     * Statistiche prodotti
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as total')
            ->addSelect('SUM(CASE WHEN p.avaiable = true AND p.out = false THEN 1 ELSE 0 END) as in_magazzino')
            ->addSelect('SUM(CASE WHEN p.out = true THEN 1 ELSE 0 END) as fuori_magazzino');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'in_magazzino' => (int) $result['in_magazzino'],
            'fuori_magazzino' => (int) $result['fuori_magazzino']
        ];
    }

    /**
     * Trova prodotto per NFC ID
     */
    public function findByNfcId(int $nfcId): ?Prodotto
    {
        return $this->findOneBy(['nfcId' => $nfcId]);
    }

    /**
     * Ottiene tutti gli scaffali unici
     */
    public function getScaffali(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.scaffale')
            ->where('p.scaffale IS NOT NULL')
            ->andWhere('p.scaffale != :empty')
            ->setParameter('empty', '')
            ->orderBy('p.scaffale', 'ASC');

        $result = $qb->getQuery()->getScalarResult();
        
        return array_column($result, 'scaffale');
    }

    //    /**
    //     * @return Prodotto[] Returns an array of Prodotto objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Prodotto
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
