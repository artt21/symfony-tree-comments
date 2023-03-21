<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\ChildCommentType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;


class CommentController extends AbstractController
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

    }

//    /**
//     * @Route("/comments", name="app_comments")
//     */
//    public function indexAction(Request $request): Response
//    {
//
//        $user = $this->getUser();
//
//        $parentComment = new Comment();
//        $childComment = new Comment();
//
//        $parentCommentForm = $this->createForm(CommentType::class, $parentComment, [
//            'csrf_token_id' => 'parent_comment_csrf_id',
//        ]);
//
//        $childCommentForm = $this->createForm(CommentType::class, $childComment);
//
//        $submittedToken = $request->request->get('child_csrf');
//
//        $isCsrfTokenValid = $this->isCsrfTokenValid('child_csrf_token', $submittedToken);
//
//        $parentCommentForm->handleRequest($request);
//        $childCommentForm->handleRequest($request);
//
//        if ($parentCommentForm->isSubmitted() && $parentCommentForm->isValid() || $childCommentForm->isSubmitted() && $isCsrfTokenValid) {
//
//            $this->addComment($parentComment, $user, $request->request->get('parent_id') ?? null);
//
//            $this->addFlash('success', 'Commentary added');
//
//            return $this->redirectToRoute('app_comments');
//        }
//
////        if ($childCommentForm->isSubmitted() && $childCommentForm->isValid()) {
////
////            $this->addComment($childComment, $user, $request->request->get('parent_id') ?? null);
////
////            $this->addFlash('success', 'Commentary added');
////
////            return $this->redirectToRoute('app_comments');
////        }
//
//        $allComments = $this->entityManager->getRepository(Comment::class)->findAll();
//
//        $sortedComments = $this->sortComments($allComments);
//
//        return $this->renderForm('comments/index.html.twig', [
//            'parentCommentForm' => $parentCommentForm,
//            'childCommentForm' => $childCommentForm,
//            'sortedComments' => $sortedComments,
//        ]);
//
//    }

    /**
     * @Route("/comments", name="app_comments")
     */
    public function indexAction(Request $request): Response
    {

        $user = $this->getUser();

        $comment = new Comment();

        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        $submittedChildToken = $request->request->get('child_csrf');
        $submittedParentToken = $request->request->get('parent_csrf');

        $isChildCsrfTokenValid = $this->isCsrfTokenValid('child_csrf_token', $submittedChildToken);
        $isParentCsrfTokenValid = $this->isCsrfTokenValid('parent_csrf_token', $submittedParentToken);

        if ($commentForm->isSubmitted() && $commentForm->isValid() && ($isParentCsrfTokenValid || $isChildCsrfTokenValid)) {

            $this->addComment($comment, $user, $request->request->get('parent_id') ?? null);

            $this->addFlash('success', 'Commentary added');

            return $this->redirectToRoute('app_comments');
        }

        $allComments = $this->entityManager->getRepository(Comment::class)->findAll();

        $sortedComments = $this->sortComments($allComments);

        return $this->renderForm('comments/index.html.twig', [
            'commentForm' => $commentForm,
            'sortedComments' => $sortedComments,
        ]);

    }

    public function addComment(Comment $comment, ?UserInterface $user, ?int $parentId = null): void
    {

        $comment->setUser($user);
        $comment->setParentId($parentId);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

    }

    /**
     * @Route("/admin/comments/delete/{id}", name="delete_comment")
     */
    public function deleteComment(int $id): Response
    {

        $comment = $this->entityManager->getRepository(Comment::class)->find($id);

        $comment->setIsDeleted(1);

        $this->entityManager->flush();

        $this->addFlash('success', 'Deleted successfully');

        return $this->redirectToRoute('app_comments');

    }

    private function sortComments(array $commentaries, ?int $parentId = null): array
    {

        $arr = [];

        foreach ($commentaries as $commentary) {
            if ($commentary->getParentId() == $parentId) {
                $children = $this->sortComments($commentaries, $commentary->getId());
                if ($children) {
                    $commentary->setChildren($children);
                }
                $arr[] = $commentary;
            }
        }

        return $arr;

    }

}

