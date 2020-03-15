<?php

namespace App\DataFixtures;

use App\Entity\Profil;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder ;
    public function __construct(UserPasswordEncoderInterface $encoder){
        $this->encoder = $encoder;
    }
    public function load(ObjectManager $manager)
    {
        $tabrole = ['ADMIN_SYSTEM','ADMIN','CAISSIER','PARTENAIRE','ADMIN_PARTENAIRE','CAISSIER_PARTENAIRE'];

        for ($i = 0 ; $i <count($tabrole)  ; $i++){
            $profil = new Profil() ;
            $profil->setLibelle($tabrole[$i]);
            $manager->persist($profil);

            if($i == 0){
                $user = new User();
                $user->setUsername("Root")
                     ->setPassword($this->encoder->encodePassword($user ,"toor"))
                     ->setProfil($profil)
                     ->setRoles(['ROLE_'.$tabrole[$i]])
                     ->setIsActif(true);
                $manager->persist($user);
            }
        }

        $manager->flush();
    }
}
