<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Problem;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResponseController extends AbstractController{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/addResponse', name: 'app_response_add', methods: ['POST'])]
    public function addResponse(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        $response = new Response();
        $response->setSolution($data['solution']);
    
        $problemId = $data['problem_id'];
        $problem = $entityManager->getRepository(Problem::class)->find($problemId);
    
        if (!$problem) {
            return new JsonResponse(['message' => 'Problem not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $response->setProblem($problem);

        $userId = $data['user_id'];
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
         }
        $response->setUser($user);
    
        $entityManager->persist($response);
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Response added successfully'], JsonResponse::HTTP_CREATED);
    }
    
    

    #[Route('/response/{id}', name: 'app_response_delete',methods : ['DELETE'])]
    public function deleteResponse($id ,  EntityManagerInterface $entityManager)
    {
        $response = $entityManager->getRepository(Response::class)->find($id);

        $entityManager->remove($response);
        $entityManager->flush();

        return $this->json([
            'id' => $response->getId(),
            'solution' => $response->getSolution(),
            'problem' => [
                'id' => $response->getProblem()->getId(),
                'title' => $response->getProblem()->getTitle(),
                'description' => $response->getProblem()->getDescription(),
                'image' => $response->getProblem()->getImage(),
            ],
            'user' => [
                'id' => $response->getUser()->getId(),
                'email' => $response->getUser()->getEmail(),
                'roles' => $response->getUser()->getRoles(),
            ],
        ]);
    }
    
    #[Route('/response/{id}', name: 'app_response_update',methods : ['PUT'])]
    public function updateResponse($id , Request $request, EntityManagerInterface $entityManager)
    {
        $response = $entityManager->getRepository(Response::class)->find($id);

        $response->setSolution($request->request->get('solution'));
        $response->setDateModified(new \DateTime($request->request->get('dateModified')));
        $entityManager->flush();

        return $this->json([
            'id' => $response->getId(),
            'solution' => $response->getSolution(),
            'problem' => [
                'id' => $response->getProblem()->getId(),
                'title' => $response->getProblem()->getTitle(),
                'description' => $response->getProblem()->getDescription(),
                'image' => $response->getProblem()->getImage(),
            ],
            'user' => [
                'id' => $response->getUser()->getId(),
                'email' => $response->getUser()->getEmail(),
                'roles' => $response->getUser()->getRoles(),
            ],
        ]);
    }

    #[Route('/response/{id}', name: 'app_response_get',methods : ['GET'])]
    public function getResponse($id ,  EntityManagerInterface $entityManager)
    {
        $response = $entityManager->getRepository(Response::class)->find($id);

        return $this->json([
            'id' => $response->getId(),
            'solution' => $response->getSolution(),
            'problem' => [
                'id' => $response->getProblem()->getId(),
                'title' => $response->getProblem()->getTitle(),
                'description' => $response->getProblem()->getDescription(),
                'image' => $response->getProblem()->getImage(),
            ],
            'user' => [
                'id' => $response->getUser()->getId(),
                'email' => $response->getUser()->getEmail(),
                'roles' => $response->getUser()->getRoles(),
            ],
        ]);
    }

    #[Route('/responses', name: 'app_response', methods: ['GET'])]
    public function listResponses(EntityManagerInterface $entityManager)
    {
        $responses = $entityManager->getRepository(Response::class)->findAll();
    
        $responseArray = [];
    
        foreach ($responses as $response) {
            $responseArray[] = [
                'id' => $response->getId(),
                'solution' => $response->getSolution(),
                'problem' => [
                    'id' => $response->getProblem()->getId(),
                    'title' => $response->getProblem()->getTitle(),
                    'description' => $response->getProblem()->getDescription(),
                    'image' => $response->getProblem()->getImage(),
                ],
                'user' => [
                    'id' => $response->getUser()->getId(),
                    'email' => $response->getUser()->getEmail(),
                    'roles' => $response->getUser()->getRoles(),
                ],
            ];
        }
    
        return $this->json($responseArray);
    }
    
    #[Route('/responsesbyProblem/{problemId}', name: 'app_responses_by_problem', methods: ['GET'])]
    public function getResponsesByProblemId(Request $request, string $problemId, EntityManagerInterface $entityManager)
    {
        // Get the problem entity based on the provided problem ID
        $problem = $entityManager->getRepository(Problem::class)->find($problemId);
        
        // Check if the problem exists
        if (!$problem) {
            // Return an error response if the problem is not found
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }
        
        // Get the responses associated with the problem
        $responses = $problem->getResponses();
        
        // Create an array to hold the response data
        $responseData = [];
        
        foreach ($responses as $response) {
            // Get the user associated with the response
            $user = $response->getUser();
            
            // Build the response data array
            $responseData[] = [
                'id' => $response->getId(),
                'solution' => $response->getSolution(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    // Add any other user properties you want to include
                ],
            ];
        }
        
        // Return the JSON response
        return new JsonResponse($responseData);
    }    
}