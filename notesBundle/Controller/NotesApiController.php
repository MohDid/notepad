<?php

namespace notesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\aController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use notesBundle\Entity\Category;
use notesBundle\Entity\Note;


class NotesApiController extends Controller
{
  /**
   * @Route(
   *        path = "/api/note/{id}",
   *        name = "get_note",
   *        defaults = { "page" = "1" },
   *        requirements = { "page" = "\d*" }
   * )
   * @Method({"GET"})
   */
  public function getNoteAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $note = $em->getRepository('notesBundle:Note')
              ->findOneById($request->get('id'));
    /* @var $note Note */
    if (empty($note)) {
            return new JsonResponse(['(404) message' => 'Pas de note trouvée pour cet ID'], Response::HTTP_NOT_FOUND);
    }
    $formatted = [
        'id' => $note->getID(),
        'title' => $note->getTitle(),
        'date' => $note->getDate()->format('d-m-Y'),
        'content' => $note->getContent(),
        'category' => $note->getCategory()->getName(),
    ];
    return new JsonResponse($formatted);
  }

  /**
   * @Route(
   *        path = "/api/notes",
   *        name = "get_notes"
   * )
   * @Method({"GET"})
   */
  public function getNotesAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $listNotes = $em->getRepository('notesBundle:Note')
              ->findBy(
                array(),
                array('date' => 'desc')
              );
    /* @var $note Note */
    $formatted = array();
     foreach ($listNotes as $note) {
       $formatted[] = array(
                       'id' => $note->getID(),
                       'title' => $note->getTitle(),
                       'date' => $note->getDate()->format('d-m-Y'),
                       'content' => $note->getContent(),
                       'category' => $note->getCategory()->getName(),
                    );
     }

    return new JsonResponse($formatted);
  }

  /**
   * @Route(
   *        path = "/api/note/post",
   *        name = "post_note"
   * )
   * @Method({"POST"})
   */
  public function postNoteAction(Request $request)
  {
    $note = new Note();
    $category = new Category();
    $em = $this->getDoctrine()->getManager();

    $jsonNote = json_decode($request->getContent(), true);
    if(!$jsonNote){
      return new JsonResponse(['(ERR) message'=> 'Contenu Json non valide!']);
    }
      $note->setTitle($jsonNote['title']);
      $note->setDate(new \DateTime($jsonNote['date']));
      $note->setContent($jsonNote['content']);
      $category = $em->getRepository('notesBundle:Category')
         ->findOneByName($jsonNote['category']);
      if(!$category){
        return new JsonResponse(["(ERR) message" => "Cette catégorie n'existe pas!"]);
      }
      $note->setCategory($category);

    $em->persist($note);
    $em->flush();
    $formatted = ['(200) message'=> 'Note ajoutee!'];
    return new JsonResponse($formatted);
  }

  /**
   * @Route(
   *        path = "/api/note/del/{id}",
   *        name = "delete_note",
   *        defaults = { "page" = "1" },
   *        requirements = { "page" = "\d*" }
   * )
   * @Method({"DELETE"})
   */
  public function deleteNoteAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $note = $em->getRepository('notesBundle:Note')
              ->findOneById($request->get('id'));

    if (empty($note)) {
      return new JsonResponse(['(404) message' => 'Pas de note trouvée pour cet ID'], Response::HTTP_NOT_FOUND);
    }
    $em->remove($note);
    $em->flush();
    $formatted = ['(200) message' => 'Suppression réussie!'];
    return new JsonResponse($formatted);
  }

  /**
   * @Route(
   *        path = "/api/note/put/{id}",
   *        name = "put_note",
   *        defaults = { "page" = "1" },
   *        requirements = { "page" = "\d*" }
   * )
   * @Method({"PUT"})
   */
  public function putNoteAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $note = $em->getRepository('notesBundle:Note')
              ->findOneById($request->get('id'));
    if (empty($note)) {
      return new JsonResponse(['(404) message' => 'Pas de note trouvée pour cet ID'], Response::HTTP_NOT_FOUND);
    }
    $jsonNote = json_decode($request->getContent(), true);
    if (array_key_exists('title', $jsonNote))
      $note->setTitle($jsonNote['title']);
    if (array_key_exists('date', $jsonNote))
      $note->setDate(new \DateTime($jsonNote['date']));
    if (array_key_exists('content', $jsonNote))
      $note->setContent($jsonNote['content']);
    if (array_key_exists('category', $jsonNote))
    {
      $category = $em->getRepository('notesBundle:Category')
                 ->findOneByName($jsonNote['category']);
      if(!$category){
        return new JsonResponse(["(ERR) message" => "Cette catégorie n'existe pas!"]);
      }
      $note->setCategory($category);
    }
    $em->persist($note);
    $em->flush();
    $formatted = ['(200) message' => 'Note mise à jour!'];
    return new JsonResponse($formatted);
  }
}
