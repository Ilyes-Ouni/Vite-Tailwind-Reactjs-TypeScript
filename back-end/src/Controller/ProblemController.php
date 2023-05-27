<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Problem;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\ProblemRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ProblemController extends AbstractController
{
/*    #[Route('/problems', name: 'app_problem', methods: ['GET'])]
    public function listProblems(ProblemRepository $problemRepository): JsonResponse
    {
        $problems = $problemRepository->findAll();
    
        $responseData = [];
    
        foreach ($problems as $problem) {
            $user = $problem->getUser(); // Get the user associated with the problem
    
            $responses = [];
            foreach ($problem->getResponses() as $response) {
                $responses[] = [
                    'id' => $response->getId(),
                    'solution' => $response->getSolution(),
                ];
            }
    
            $responseData[] = [
                'id' => $problem->getId(),
                'title' => $problem->getTitle(),
                'description' => $problem->getDescription(),
                'topic_id' => $problem->getTopic(),
                'image' => $problem->getImage(),
                'responses' => $responses,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ],
            ];
        }
    
        return $this->json($responseData);
    }
    */
    
    #[Route('/problemImage/{imageName}', name: 'app_problem_image', methods: ['GET'])]
    public function getImageByName(Request $request, string $imageName): Response
    {
        $imagesDirectory = $this->getParameter('image_directory');
        $imagePath = $imagesDirectory . '/' . $imageName;

        if (file_exists($imagePath)) {
            try {
                return new BinaryFileResponse($imagePath);
            } catch (FileNotFoundException $exception) {
                return new Response('Image not found', Response::HTTP_NOT_FOUND);
            }
        }

        return new Response('Image not found', Response::HTTP_NOT_FOUND);
    }


    #[Route('/problems/{topic_id}', name: 'app_problem', methods: ['GET'])]
    public function listProblems(ProblemRepository $problemRepository, $topic_id): JsonResponse
    {
        $problems = $problemRepository->findByTopic(['topic' => $topic_id]);

        $responseData = [];

        foreach ($problems as $problem) {
            $user = $problem->getUser(); // Récupérer l'utilisateur associé au problème

            $responses = [];
            foreach ($problem->getResponses() as $response) {
                $responses[] = [
                    'id' => $response->getId(),
                    'solution' => $response->getSolution(),
                ];
            }

            $responseData[] = [
                'id' => $problem->getId(),
                'title' => $problem->getTitle(),
                'description' => $problem->getDescription(),
                'image' => $problem->getImage(),
                'responses' => $responses,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ],
            ];
        }

        return $this->json($responseData);
    }

    

    #[Route('/addProblem', name: 'app_problem_add', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $problem = new Problem();
        $problem->setTitle($request->request->get('title'));
        $problem->setDescription($request->request->get('description'));

        $topicId = $request->request->get('topic_id');
        $problem->setTopic($topicId);
        // Assuming that the request body contains a user ID, you can load the
        // user entity from the database and set it on the problem entity:
        $userId = $request->request->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        $problem->setUser($user);

        $file = $request->files->get('image');
        if ($file) {
            $filename = md5(uniqid()) . '.' . $file->guessExtension();
            $file->move($this->getParameter('image_directory'), $filename);
            $problem->setImage($filename);
        }

        $entityManager->persist($problem);
        $entityManager->flush();

        return $this->json('Inserted successfully', Response::HTTP_CREATED);
    }

    #[Route('/problem/{id}', name: 'app_problem_delete',methods : ['DELETE'])]
    public function deleteProblem($id ,  EntityManagerInterface $entityManager)
    {
        $problem = $entityManager->getRepository(Problem::class)->find($id);

        $entityManager->remove($problem);
        $entityManager->flush();

        return $this->json([
            'id' => $problem->getId(),
            'title' => $problem->getTitle(),
            'description' => $problem->getDescription(),
        ]);
    }

    #[Route('/problem/{id}', name: 'app_problem_get', methods: ['GET'])]
    public function getProblem($id, EntityManagerInterface $entityManager): JsonResponse
    {
        $problem = $entityManager->getRepository(Problem::class)->find($id);
    
        if (!$problem) {
            return $this->json(['message' => 'Problem not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $problem->getUser(); // Get the user associated with the problem
    
        $responses = [];
        foreach ($problem->getResponses() as $response) {
            $responses[] = [
                'id' => $response->getId(),
                'solution' => $response->getSolution(),
            ];
        }
    
        return $this->json([
            'id' => $problem->getId(),
            'title' => $problem->getTitle(),
            'description' => $problem->getDescription(),
            'image' => $problem->getImage(),
            'responses' => $responses,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ]);
    }
    

    #[Route('/problem/{id}', name: 'app_problem_update',methods : ['PUT'])]
    public function updateProblem($id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $problem = $entityManager->getRepository(Problem::class)->find($id);
      
        $problem->setTitle($request->request->get('title'));
        $problem->setDescription($request->request->get('description'));


        // Assuming that the request body contains a developer ID, you can load the
        // developer entity from the database and set it on the problem entity:


        $file = $request->files->get('image');
        if ($file) {
            $filename = md5(uniqid()) . '.' . $file->guessExtension();
            $file->move($this->getParameter('image_directory'), $filename);
            $problem->setImage($filename);
        }

        $entityManager->flush();

        $response = new Response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $this->json('Updated successfully');
    }
}
