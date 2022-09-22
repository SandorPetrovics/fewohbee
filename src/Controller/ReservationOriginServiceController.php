<?php

declare(strict_types=1);

/*
 * This file is part of the guesthouse administration package.
 *
 * (c) Alexander Elchlepp <info@fewohbee.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ReservationOrigin;
use App\Service\CSRFProtectionService;
use App\Service\ReservationOriginService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservationorigin')]
class ReservationOriginServiceController extends AbstractController
{
    /**
     * Index-View.
     *
     * @return mixed
     */
    #[Route('/', name: 'reservationorigin.overview', methods: ['GET'])]
    public function indexAction(ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $origins = $em->getRepository(ReservationOrigin::class)->findAll();

        return $this->render('ReservationOrigin/index.html.twig', [
            'origins' => $origins,
        ]);
    }

    /**
     * Show single entity.
     *
     * @param $id
     *
     * @return mixed
     */
    #[Route('/{id}/get', name: 'reservationorigin.get.origin', methods: ['GET'], defaults: ['id' => '0'])]
    public function getAction(ManagerRegistry $doctrine, CSRFProtectionService $csrf, $id)
    {
        $em = $doctrine->getManager();
        $origin = $em->getRepository(ReservationOrigin::class)->find($id);

        return $this->render('ReservationOrigin/reservationorigin_form_edit.html.twig', [
            'origin' => $origin,
            'token' => $csrf->getCSRFTokenForForm(),
        ]);
    }

    /**
     * Show form for new entity.
     *
     * @return mixed
     */
    #[Route('/new', name: 'reservationorigin.new.origin', methods: ['GET'])]
    public function newAction(ManagerRegistry $doctrine, CSRFProtectionService $csrf)
    {
        $em = $doctrine->getManager();

        $origin = new ReservationOrigin();
        $origin->setId('new');

        return $this->render('ReservationOrigin/reservationorigin_form_create.html.twig', [
            'origin' => $origin,
            'token' => $csrf->getCSRFTokenForForm(),
        ]);
    }

    /**
     * Create new entity.
     *
     * @return mixed
     */
    #[Route('/create', name: 'reservationorigin.create.origin', methods: ['POST'])]
    public function createAction(ManagerRegistry $doctrine, CSRFProtectionService $csrf, ReservationOriginService $ros, Request $request)
    {
        $error = false;
        if ($csrf->validateCSRFToken($request)) {
            $origin = $ros->getOriginFromForm($request, 'new');

            // check for mandatory fields
            if (0 == strlen($origin->getName())) {
                $error = true;
                $this->addFlash('warning', 'flash.mandatory');
            } else {
                $em = $doctrine->getManager();
                $em->persist($origin);
                $em->flush();

                // add succes message
                $this->addFlash('success', 'reservationorigin.flash.create.success');
            }
        }

        return $this->render('feedback.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * update entity end show update result.
     *
     * @param $id
     *
     * @return mixed
     */
    #[Route('/{id}/edit', name: 'reservationorigin.edit.origin', methods: ['POST'], defaults: ['id' => '0'])]
    public function editAction(ManagerRegistry $doctrine, CSRFProtectionService $csrf, ReservationOriginService $ros, Request $request, $id)
    {
        $error = false;
        if ($csrf->validateCSRFToken($request)) {
            $origin = $ros->getOriginFromForm($request, $id);
            $em = $doctrine->getManager();

            // check for mandatory fields
            if (0 == strlen($origin->getName())) {
                $error = true;
                $this->addFlash('warning', 'flash.mandatory');
                // stop auto commit of doctrine with invalid field values
                $em->clear(ReservationOrigin::class);
            } else {
                $em->persist($origin);
                $em->flush();

                // add succes message
                $this->addFlash('success', 'reservationorigin.flash.edit.success');
            }
        }

        return $this->render('feedback.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * delete entity.
     *
     * @param $id
     *
     * @return string
     */
    #[Route('/{id}/delete', name: 'reservationorigin.delete.origin', methods: ['GET', 'POST'])]
    public function deleteAction(CSRFProtectionService $csrf, ReservationOriginService $ros, Request $request, $id)
    {
        if ('POST' == $request->getMethod()) {
            if ($csrf->validateCSRFToken($request, true)) {
                $origin = $ros->deleteOrigin($id);
                if ($origin) {
                    $this->addFlash('success', 'reservationorigin.flash.delete.success');
                }
            }

            return new Response('', Response::HTTP_NO_CONTENT);
        } else {
            // initial get load (ask for deleting)
            return $this->render('common/form_delete_entry.html.twig', [
                'id' => $id,
                'token' => $csrf->getCSRFTokenForForm(),
            ]);
        }
    }
}
