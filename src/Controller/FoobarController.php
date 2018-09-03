<?php

namespace App\Controller;

use App\Entity\Foobar;
use App\Form\FoobarType;
use App\Repository\FoobarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/foobar")
 */
class FoobarController extends AbstractController
{
    /**
     * @Route("/", name="foobar_index", methods="GET")
     */
    public function index(FoobarRepository $foobarRepository): Response
    {
        return $this->render('foobar/index.html.twig', ['foobars' => $foobarRepository->findAll()]);
    }

    /**
     * @Route("/new", name="foobar_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $foobar = new Foobar();
        $form = $this->createForm(FoobarType::class, $foobar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($foobar);
            $em->flush();

            return $this->redirectToRoute('foobar_index');
        }

        return $this->render('foobar/new.html.twig', [
            'foobar' => $foobar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="foobar_show", methods="GET")
     */
    public function show(Foobar $foobar): Response
    {
        return $this->render('foobar/show.html.twig', ['foobar' => $foobar]);
    }

    /**
     * @Route("/{id}/edit", name="foobar_edit", methods="GET|POST")
     */
    public function edit(Request $request, Foobar $foobar): Response
    {
        $form = $this->createForm(FoobarType::class, $foobar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('foobar_edit', ['id' => $foobar->getId()]);
        }

        return $this->render('foobar/edit.html.twig', [
            'foobar' => $foobar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="foobar_delete", methods="DELETE")
     */
    public function delete(Request $request, Foobar $foobar): Response
    {
        if ($this->isCsrfTokenValid('delete'.$foobar->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($foobar);
            $em->flush();
        }

        return $this->redirectToRoute('foobar_index');
    }
}
