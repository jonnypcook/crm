<?php

namespace Client\Repository;
 
use Doctrine\ORM\EntityRepository;
use Client\Entity;
 
class Client extends EntityRepository
{
    public function findByName($name) {
        // First get the EM handle
        // and call the query builder on it
        $query = $this->_em->createQuery("SELECT c FROM Client\Entity\Client c WHERE c.name='{$name}'");
        return $query->getResult();
    }


}

