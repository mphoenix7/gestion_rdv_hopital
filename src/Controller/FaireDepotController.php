<?php

namespace App\Controller;


use App\Entity\Depot;
use App\Repository\CompteRepository;
use App\Repository\PartenaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class FaireDepotController extends AbstractController
{
    public function __construct(TokenStorageInterface $token)
    {
        $this->token = $token;
    }

    /**
     * @Route("/api/fairedepot", name="faire_depot")
     */
    public function index(Request $request , CompteRepository $compteRepository , SerializerInterface $serializer ,EntityManagerInterface $em)
    {
        $json = $request->getContent();
        $data = $serializer->deserialize($json , Depot::class , 'json');

        if(isset($data)){
            $value = $data->getCompte()->getNumeroCompte();
            $foundnumcompte = $compteRepository->findOneBy(['numeroCompte' => $value]);
            if( is_null($foundnumcompte) == false){

                if($data->getMontant() > 0){
                    $depot = new Depot();
                    $depot->setMontant($data->getMontant())
                          ->setDateDepot(new \DateTime())
                          ->setUserwhodid($this->token->getToken()->getUser())
                          ->setCompte($data->getCompte());
                    $em->persist($depot);
                    $data->getCompte()->setSolde($data->getMontant() + $data->getCompte()->getSolde());
                    $em->flush();

                    $data =[
                        'statu' => 200 ,
                         "message" => "Le dépot a bien été effectué"
                    ];
                    return new JsonResponse($data,200);

                }
                else{
                     $data = ['message' => "le montant ne peut pas être négatif"];

                     return new JsonResponse($data);
                }
            }
            else{
                $data = [
                    'statu' => 404,
                    'message' => "Ce numero de compte n'existe pas "];

                return new JsonResponse($data , 404);
            }
        }
    }
}
