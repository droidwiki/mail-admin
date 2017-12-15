<?php

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function findByDomain(Domain $domain)
    {
        return $this->createQueryBuilder('users')
            ->where('users.domain = :domain')->setParameter('domain', $domain->getDomain())
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneByUsername($changedUsername, $changedDomain)
    {
        return $this->findOneBy([
            'username' => $changedUsername,
            'domain' => $changedDomain,
        ]);
    }
}
