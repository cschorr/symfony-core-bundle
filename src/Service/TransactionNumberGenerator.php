<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to generate unique transaction numbers with collision prevention.
 */
class TransactionNumberGenerator
{
    private const string PREFIX = 'TRX';

    private const int MAX_RETRIES = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Generate a unique transaction number with format: TRX-YYYY-NNNN
     * Where NNNN is a 4-digit sequential number that resets each year.
     *
     * @throws \RuntimeException if unable to generate unique number after retries
     */
    public function generate(): string
    {
        $year = date('Y');
        $retries = 0;

        while ($retries < self::MAX_RETRIES) {
            // Get the count of transactions for this year to generate sequential number
            $sequentialNumber = $this->getNextSequentialNumber($year);

            $transactionNumber = sprintf(
                '%s-%s-%s',
                self::PREFIX,
                $year,
                str_pad((string) $sequentialNumber, 4, '0', STR_PAD_LEFT)
            );

            // Check uniqueness
            if (!$this->exists($transactionNumber)) {
                return $transactionNumber;
            }

            ++$retries;
        }

        throw new \RuntimeException(sprintf('Failed to generate unique transaction number after %d retries', self::MAX_RETRIES));
    }

    /**
     * Get the next sequential number for the given year.
     */
    private function getNextSequentialNumber(string $year): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Transaction::class, 't')
            ->where('t.transactionNumber LIKE :prefix')
            ->setParameter('prefix', self::PREFIX . '-' . $year . '%');

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count + 1;
    }

    /**
     * Check if a transaction number already exists.
     */
    private function exists(string $transactionNumber): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(t.id)')
            ->from(Transaction::class, 't')
            ->where('t.transactionNumber = :number')
            ->setParameter('number', $transactionNumber);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
