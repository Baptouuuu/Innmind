<?php

namespace Innmind\AppBundle\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class InfoController extends Controller
{
    public function indexAction()
    {
        $data = [
            'app' => 'innmind.frontend',
            'version' => $this->container->getParameter('api.version.current'),
        ];

        $resp = new JsonResponse($data);
        return $resp
            ->setPublic()
            ->setSharedMaxAge(24*3600)
            ->setMaxAge(24*3600);
    }
}
