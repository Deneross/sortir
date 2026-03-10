<?php


namespace App\Controller;

use App\Repository\CampusRepository;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
final class ApiCampusController extends AbstractController
{
    #[Route('/campus', name: 'api_campus', methods: ['GET'])]
    public function list(CampusRepository $campusRepository, SerializerInterface $serializer): JsonResponse
    {
        $campus = $campusRepository->findBy([], ['name' => 'ASC']);

        $data = array_map(static fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
        ], $campus);



        return new JsonResponse($data);
    }
}
