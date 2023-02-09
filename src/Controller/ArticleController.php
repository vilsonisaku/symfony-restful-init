<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{

    public function __construct(private ManagerRegistry $doctrine, EntityManagerInterface $entityManager)
    {
    }

    #[Route('/article', name: 'app_article')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ArticleController.php',
        ]);
    }


    #[Route('add/article', name: 'add_article', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = $request->request->all();
        $title = $data['title'];
        $description = $data['description'];
        $image = $data['image'];
        if (empty($title) || empty($description) || empty($image)) {
            throw new NotFoundHttpException('Expecting mandatory parameters!');
        }

        $this->doctrine->getRepository(Article::class)->saveArticle($title, $description, $image);

        return new JsonResponse(['status' => 'Article created successfully!'], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
    }

    #[Route("/get/article/{id}", name: "get_one_article", methods: ["GET"])]
    public function get($id): JsonResponse
    {
        $article = $this->doctrine->getRepository(Article::class)->findOneBy(['id' => $id]);
        if ($article) {
            $data = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'description' => $article->getDescription(),
                'image' => $article->getImage(),
            ];

            return new JsonResponse($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'Article with id '.$id. ' does not exists!'
            ]);
        }
    }


    #[Route("/get/articles", name:"get_all_articles", methods:["GET"])]

    public function getAll(): JsonResponse
    {
        $articles = $this->doctrine->getRepository(Article::class)->findAll();
        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'description' => $article->getDescription(),
                'image' => $article->getImage(),
            ];
        }

        return new JsonResponse($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


    #[Route("/update/article/{id}", name: 'update_article', methods: ["PUT"])]
    public function update($id, Request $request): JsonResponse
    {
        $article = $this->doctrine->getRepository(Article::class)->findOneBy(['id' => $id]);
        $data = $request->request->all();
        empty($data['title']) ? true : $article->setTitle($data['title']);
        empty($data['description']) ? true : $article->setDescription($data['description']);
        empty($data['image']) ? true : $article->setImage($data['image']);

        $updatedArticle = $this->doctrine->getRepository(Article::class)->updateArticle($article);

        return new JsonResponse($updatedArticle->toArray(), \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


    #[Route("/delete/article/{id}", name: "delete_article", methods: ["DELETE"])]
    public function delete($id): JsonResponse
    {
        $article = $this->doctrine->getRepository(Article::class)->findOneBy(['id' => $id]);
        $this->doctrine->getRepository(Article::class)->removeUser($article);

        return new JsonResponse(['status' => 'Article deleted'], \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }

}
