<?php

namespace notesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use notesBundle\Entity\Category;
use notesBundle\Entity\Note;
//Nécessaires pour les formulaires sur Symfony 3!
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class NotesController extends Controller
{
  /**
   * @Route(
   *        path = "/{page}",
   *        name = "notes_index",
   *        defaults = { "page" = "1" },
   *        requirements = { "page" = "\d*" }
   * )
   */
  public function indexAction($page)
  {
    //récuperation de la liste des notes à partir de Doctrine Repository ,
    //en classant celles-ci dans un ordre décroissant selon leur dates
    $listNotes = $this->getDoctrine()
          ->getRepository('notesBundle:Note')
          ->findby(
              array(),
              array('date' => 'desc')
            );
    //On passe le tablau contenant les notes à notre twig Index
    //index.html.twig est la page principale de notepad et permet d'afficher
    //toutes les notes si elles existes.
    return $this->render('notesBundle:Notes:index.html.twig', $mesVars = array(
      'listNotes' => $listNotes
    ));
  }

  /**
   * @Route(
   *        path = "/notes/{id}",
   *        name = "notes_view",
   *        defaults = { "id" = "1" },
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function viewAction($id)
  {
    //Testing purposes
    $note = array(
      'id' => $id,
      'title' => 'Le Titre',
      'content' => 'Ce texte est pour tester le contenu, bla bla..',
      'date' => new \Datetime(),
      'category' => 'test'
    );
     return $this->render('notesBundle:Notes:index.html.twig', $mesVars = array(
      'note' => $note
    ));
  }

  /**
   * @Route(
   *        path = "/add",
   *        name = "notes_create"
   * )
   */
  public function addAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();

    $categories = $em->getRepository('notesBundle:Category')->findAll();

    $note = new Note();
    //creation d'une form contenant les données de la note avec FormBuilder
    $form = $this->createFormBuilder($note)
      ->add('Title',   TextType::class)
      ->add('Content',   TextareaType::class)
      ->add('Date',   DateType::class)
      ->add('Category', ChoiceType::class, array(
          'choices'    => $categories,
          'choice_label' => function($cat, $key, $index){
              return $cat->getName();
            })
          )
      ->add('Sauvegarder',   SubmitType::class)
      ->getForm();
      //on récupére la requete sur $form
      $form->handleRequest($request);

      $note = $form->getData();
      //si le formulaire à été validé, on sauvegarde les données
      if($form->isValid()){
          $em->persist($note);
          $em->flush();

        return $this->redirect($this->generateUrl('notes_index', array(
          'id' => $note->getId())));
      }
    //On passe le formulaire au twig add
    //add.html.twig contient le header indiquant que c'est une page d'ajoute
    //et permet de faire un include du twig Form qui lui contient les champs de form
    return $this->render('notesBundle:Notes:add.html.twig', array(
      'form' => $form->createView(),
    ));
  }

  /**
   * @Route(
   *        path = "/edit/{id}",
   *        name = "notes_edit",
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function editAction($id, Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $categories = $em->getRepository('notesBundle:Category')->findAll();

    $note = new Note();
    $note = $em->getRepository('notesBundle:Note')->findOneById($id);

    $form = $this->createFormBuilder($note)
      ->add('Title',   TextType::class, array(
          'data' => $note->getTitle(),
      ))
      ->add('Content',   TextareaType::class, array(
          'data' => $note->getContent(),
      ))
      ->add('Date',   DateType::class, array(
          'data' => $note->getDate(),
      ))
      ->add('Category', ChoiceType::class, array(
          'choices'    => $categories,
          'choice_label' => function($cat, $key, $index){
              return $cat->getName();
            })
        )
      ->add('Sauvegarder',   SubmitType::class)
      ->getForm();

      $form->handleRequest($request);

      $note = $form->getData();
      if($form->isValid()){
          $em->persist($note);
          $em->flush();

        return $this->redirect($this->generateUrl('notes_index', array(
          'id' => $note->getId())));
      }
    return $this->render('notesBundle:Notes:edit.html.twig', array(
      'form' => $form->createView(),
    ));

  }

  /**
   * @Route(
   *        path = "/delete/{id}",
   *        name = "notes_delete",
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function deleteAction($id)
  {
    $note = $this
      ->getDoctrine()
      ->getRepository('notesBundle:Note')
      ->find($id);

      $em = $this->getDoctrine()->getManager();
      $em->remove($note);
      $em->flush();

        return $this->redirect($this->generateUrl('notes_index', array(
          'id' => $note->getId())));
  }

  /**
   * @Route(
   *        path = "/category/delete/{id}",
   *        name = "category_delete",
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function deleteCategoryAction($id)
  {
    $category = $this
      ->getDoctrine()
      ->getRepository('notesBundle:Category')
      ->find($id);

      $em = $this->getDoctrine()->getManager();
      $em->remove($category);
      $em->flush();

        return $this->redirect($this->generateUrl('category_list', array(
          'id' => $category->getId())));
  }

  /**
   * @Route(
   *        path = "/category/edit/{id}",
   *        name = "category_edit",
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function editCategoryAction($id, Request $request)
  {
    $category = new Category();
    $record = $this
      ->getDoctrine()
      ->getRepository('notesBundle:Category')
      ->find($id);

    $form = $this->createFormBuilder($category)
      ->add('Name',   TextType::class, array(
          'data' => $record->getName(),
      ))
      ->add('Sauvegarder',   SubmitType::class)
      ->getForm();

      $form->handleRequest($request);
      if($form->isValid()){
          $record->setName($category->getName());
          //on test avec $isinDB si la nouvelle catégorie fournie par l'utilisateur
          //n'existe pas sur la DB afin de la Sauvegarder,
          // sinon on envoi un message pour le signaler à l'utilisateur
          $isinDB = $this
            ->getDoctrine()
            ->getRepository('notesBundle:Category')
            ->findOneByName($category->getName());
            if(!$isinDB){
              $em = $this->getDoctrine()->getManager();
              $em->persist($record);
              $em->flush();
            }
            else {
              echo 'La catégorie '.$category->getName().' est déja enregistrée';
            }
        return $this->redirect($this->generateUrl('category_list', array(
          'id' => $category->getId())));
      }
    return $this->render('notesBundle:Notes:editCategory.html.twig', array(
      'form' => $form->createView(),
    ));
  }

  /**
   * @Route(
   *        path = "/category/{id}",
   *        name = "category_list",
   *        defaults = { "id" = "1" },
   *        requirements = { "id" = "\d+" }
   * )
   */
  public function listCategoryAction(Request $request)
  {
    $categories = $this->getDoctrine()
      ->getRepository('notesBundle:Category')
      ->findAll();
    return $this->render('notesBundle:Notes:listCategory.html.twig', array(
      'categories' => $categories
    ));
  }

  /**
   * @Route(
   *        path = "/category/add",
   *        name = "category_create"
   * )
   */
  public function addCategoryAction(Request $request)
  {
    $category = new Category();
    $form = $this->createFormBuilder($category)
      ->add('Name',   TextType::class)
      ->add('Sauvegarder',   SubmitType::class)
      ->getForm();

      $form->handleRequest($request);
      if($form->isValid()){
        $record = $this
          ->getDoctrine()
          ->getRepository('notesBundle:Category')
          ->findOneByName($category->getName());
        if(!$record) {
          $em = $this->getDoctrine()->getManager();
          $em->persist($category);
          $em->flush();
        }
        else {
          echo 'La catégorie '.$category->getName().' est déja enregistrée';
        }

        return $this->redirect($this->generateUrl('category_list', array(
          'id' => $category->getId())));
      }
    return $this->render('notesBundle:Notes:addCategory.html.twig', array(
      'form' => $form->createView(),
    ));
  }
}
