<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @ORM\Table(name="domains")
 */
class Domain
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $domain;

    public function __construct($domain)
    {
        if ($domain === null) {
            throw new \InvalidArgumentException();
        }

        $this->domain = $domain;
    }

    public function setDomain(string $domain) {
        $this->domain = $domain;
    }

    public function getDomain() {
        return $this->domain;
    }
}
