<?php
namespace App\DataPersister ;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPersister implements ContextAwareDataPersisterInterface{
    public function __construct(UserPasswordEncoderInterface $encoder , EntityManagerInterface $em)
    {
        $this->encoder = $encoder ;
        $this->em = $em;
    }
    public function supports($data, array $context = []): bool
    {
        return $data instanceof User ;
    }
    public function persist($data, array $context = [])
    {
        $user = new User();

        $data->setPassword($this->encoder->encodePassword($user , $data->getPassword()))
            ->setRoles(['ROLE_'.$data->getProfil()->getLibelle()]);
        $this->em->persist($data);
        $this->em->flush();
    }
    public function remove($data, array $context = [])
    {
         $this->em->remove($data);
         $this->em->flush();
    }
}