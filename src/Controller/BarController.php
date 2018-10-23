<?php

namespace App\Controller;

use App\Entity\Bar;
use App\Form\BarType;
use App\Form\BarData;
use App\Repository\BarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bar")
 */
class BarController extends AbstractController
{
    /**
     * @Route("/", name="bar_index", methods="GET")
     */
    public function index(BarRepository $barRepository): Response
    {
        return $this->render('bar/index.html.twig', ['bars' => $barRepository->findAll()]);
    }

    /**
     * @Route("/new", name="bar_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $barData = new BarData();
        $form = $this->createForm(BarType::class, $barData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // create entity after form is valid and fill it with data from DTO
            $bar = $barData->fill(new Bar());

            $em->persist($bar);
            $em->flush();

            return $this->redirectToRoute('bar_index');
        }

        return $this->render('bar/new.html.twig', [
            'bar' => $barData,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="bar_show", methods="GET")
     */
    public function show(Bar $bar): Response
    {
        return $this->render('bar/show.html.twig', ['bar' => $bar]);
    }

    /**
     * @Route("/{id}/edit", name="bar_edit", methods="GET|POST")
     */
    public function edit(Request $request, Bar $bar): Response
    {
        // create DTO, passing entity for extraction
        $barData = new BarData($bar);
        // pass DTO to form
        $form = $this->createForm(BarType::class, $barData);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // fill form data into entity after form is valid
            $bar = $barData->fill($bar);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('bar_edit', ['id' => $bar->getId()]);
        }

        return $this->render('bar/edit.html.twig', [
            'bar' => $bar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="bar_delete", methods="DELETE")
     */
    public function delete(Request $request, Bar $bar): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bar->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($bar);
            $em->flush();
        }

        return $this->redirectToRoute('bar_index');
    }
}
