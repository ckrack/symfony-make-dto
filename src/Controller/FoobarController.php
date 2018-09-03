<?php

namespace App\Controller;

use App\Entity\Foobar;
use App\Form\FoobarType;
use App\Form\FoobarData;
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
        // form uses DTO
        $foobarData = new FoobarData();
        $form = $this->createForm(FoobarType::class, $foobarData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            // create entity after form is valid and fill it with data from DTO
            $foobar = $foobarData->fill(new Foobar());
            $em->persist($foobar);
            $em->flush();

            return $this->redirectToRoute('foobar_index');
        }

        return $this->render('foobar/new.html.twig', [
            'foobar' => $foobarData,
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
        // create DTO, passing entity for extraction
        $foobarData = new FoobarData($foobar);
        // pass DTO to form
        $form = $this->createForm(FoobarType::class, $foobarData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // fill form data into entity after form is valid
            $foobar = $foobarData->fill($foobar);
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
