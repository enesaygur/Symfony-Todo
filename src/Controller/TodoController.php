<?php
namespace App\Controller;

use App\Entity\Todo;
use App\Form\TodoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
class TodoController extends AbstractController
{
    #[Route('/todos',name:'todos_list')]
    public function index(EntityManagerInterface $em): Response
    {
        $todos=$em->getRepository(Todo::class)->findBy([],['id'=>'DESC']);
        return $this->render('todo/index.html.twig',['todos'=>$todos]);
    }

    // public function index(EntityManagerInterface $em):Response
    // {
    //     $todos= $em->getRepository(Todo::class)->findAll();
    //     return new Response('<html><body><h1>Todo List</h1><ul>'.
    //     implode('',array_map(fn($todo)=>"<li>{$todo->getId()}-{$todo->getTitle()} :".($todo->isCompleted() ? '✅ ':'❌').
    //     " - Created At: " .$todo->getCreatedAt()->format('Y-m-d H:i')
    //     ."</li>", $todos))
    //     .'</ul></body></html>'
    //     );
    // }

    #[Route('/todos/add',name:'todos_add')]
    public function add(Request $request,EntityManagerInterface $em): Response
    {
        $todo =new Todo();
        $form = $this->createForm(TodoType::class,$todo);
        $form -> handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $todo->setCreatedAt(new \DateTimeImmutable());
            $todo->setCompleted(false);

            $em->persist($todo);
            $em->flush();
            return $this->redirectToRoute('todos_list');
        }

        return $this->render('todo/add.html.twig',[
            'form'=> $form->createView(),
        ]);
    }
    // public function add(EntityManagerInterface $em):Response
    // {
    //     $todo = new Todo();
    //     $todo -> setTitle("New Todo 4");
    //     $todo -> setCreatedAt(new \DateTimeImmutable());

    //     $em->persist($todo);
    //     $em->flush();
    //     return new Response("New Todo Added {$todo->getTitle()}");
    // }

    #[Route('/todos/{id}/toggle', name:'todos_toggle')]
    public function complete(EntityManagerInterface $em, Todo $todo): Response
    {
        if(!$todo ){
            return new Response("Todo not found",404);
        }
        $todo->setCompleted(!$todo->isCompleted());
        $em->flush();
        return $this->redirectToRoute("todos_list");
    }

    #[Route("/todos/{id}/delete", name:"todos_delete", methods: ['POST'])]
    public function deleteTodo(EntityManagerInterface $em, Todo $todo):Response
    {
        if(!$todo){
            return new Response("Todo not found",404);
        }
        $em->remove($todo);
        $em->flush();
        return $this->redirectToRoute("todos_list");
    }

    #[Route("/todos/{id}/edit", name:"todos_edit")]
    public function edit(Todo $todo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($todo)
            ->add('title', TextType::class, ['label' => 'Title', 'attr' => ['class' => 'form-control']])
            ->add('save', SubmitType::class, ['label' => 'Update', 'attr' => ['class' => 'btn btn-primary mt-3']])
            ->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em->flush();
            return $this->redirectToRoute('todos_list');
        }
        return $this->render('todo/edit.html.twig', [
            'form'=>$form->createView()
        ]);
    }

    #[Route('/todos/{id}/toggle-ajax', name:'todos_toggle_ajax', methods:['POST'])]
    public function toggleAjax(EntityManagerInterface $em, Todo $todo): JsonResponse
    {
        if(!$todo){
            return new JsonResponse(['success'=>false, 'message'=>'Todo not found'],404);
        }
        $todo->setCompleted(!$todo->isCompleted());
        $em->flush();

        return new JsonResponse([
            'success'=>true,
            'completed'=>$todo->isCompleted(),
            'id'=>$todo->getId()
        ]);
    }

    #[Route('/todos/{id}/delete-ajax',name:'todos_delete_ajax',methods:['DELETE'])]
    public function deleteAjax(EntityManagerInterface $em, Todo $todo): JsonResponse
    {
        if(!$todo){
            return new JsonResponse(['success'=>false,'message'=>'Todo not found'],404);
        }
        $id=$todo->getId();
        $em->remove($todo);
        $em->flush();
        
        return new JsonResponse([
            'success'=>true,
            'id'=>$id
        ]);
    }
}

