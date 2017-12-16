<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="users",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="unique_user", columns={"username", "domain"})
 * })
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $username;

    /**
     * @ORM\Column(type="string")
     * @ORM\ManyToOne(targetEntity="Domain")
     * @ORM\JoinColumn(name="domain", referencedColumnName="domain", unique=true)
     */
    private $domain;

    /**
     * @ORM\Column(type="string")
     */
    private $password;

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername(string $username) {
        $this->username = $username;
    }

    public function getDomain() {
        return new Domain($this->domain);
    }

    public function setDomain(Domain $domain) {
        $this->domain = $domain->getDomain();
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword(string $password) {
        $salt = substr(sha1(rand()), 0, 16);
        $hashedPassword = "{SHA512-CRYPT}" . crypt($password, "$6$$salt");
        $this->password = $hashedPassword;
    }

    public function exists() {
        return $this->getId() !== null;
    }
}
