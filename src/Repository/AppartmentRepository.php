<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Appartment;
use App\Entity\Reservation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * AppartmentRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AppartmentRepository extends EntityRepository
{
    public function loadAvailableAppartmentsForPeriod(\DateTimeInterface $start, \DateTimeInterface $end, $object)
    {
        $dateInterval = date_diff($start, $end);
        $intervall = $dateInterval->format('%a');

        $em = $this->getEntityManager();
        if ('all' == $object) {
            $appartments = $em->getRepository(Appartment::class)->findAll();
        } else {
            $appartments = $em->getRepository(Appartment::class)->findByObject($object);
        }

        $appartmentsAvailable = [];
        foreach ($appartments as $appartment) {
            $reservationsForAppartment = $em->getRepository(Reservation::class)->loadReservationsForPeriodForSingleAppartmentWithoutStartAndEndDate($start->getTimestamp(), $intervall, $appartment);
            if (null == $reservationsForAppartment) {
                $appartmentsAvailable[] = $appartment;
            }
        }

        return $appartmentsAvailable;
    }

    public function loadSumBedsMinForObject($objectId)
    {
        if ('all' === $objectId) {
            $query = $this->createQueryBuilder('u')
            ->select('SUM(u.beds_max)')
            // ->addGroupBy('u.object')
            ->getQuery();
        } else {
            $query = $this->createQueryBuilder('u')
            ->select('SUM(u.beds_max)')
            ->where('u.object = :id')
            // ->addGroupBy('u.object')
            ->setParameter('id', $objectId)
            ->getQuery();
        }

        try {
            return $query->getSingleScalarResult();
        } catch (NoResultException $ex) {
            return 0;
        }
    }

    public function findAllByProperty($propertyId = 'all'): array
    {
        if ('all' === $propertyId) {
            return $this->findAll();
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.object = :id')
            ->setParameter('id', $propertyId)
            ->getQuery()
            ->getResult();
    }
}
