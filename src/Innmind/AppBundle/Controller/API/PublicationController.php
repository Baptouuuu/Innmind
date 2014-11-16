<?php

namespace Innmind\AppBundle\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class PublicationController extends Controller
{
    /**
     * Add a new resource to the graph
     */

    public function createAction(Request $request)
    {
        $provider = $this->get('resource_token_provider');
        $provider
            ->setToken($request->headers->get('X-Token'))
            ->setURI($request->headers->get('X-Resource'));

        if (!$provider->hasToken()) {
            throw $this->createAccessDeniedException();
        }

        $publisher = $this->get('node.publisher');
        $referer = null;

        if ($provider->getToken()->hasReferer()) {
            $referer = $provider->getToken()->getReferer();
        }

        $node = $publisher->save(
            $request->request,
            $referer
        );

        $provider->clearToken();

        return new JsonResponse(
            $this
                ->get('node.normalizer')
                ->normalize($node)
        );
    }

    /**
     * Update an already exisiting node from the graph
     *
     */

    public function updateAction(Request $request, $uuid)
    {
        $provider = $this->get('resource_token_provider');
        $provider
            ->setToken($request->headers->get('X-Token'))
            ->setURI($request->headers->get('X-Resource'));

        if (!$provider->hasToken()) {
            throw $this->createAccessDeniedException();
        }

        $publisher = $this->get('node.publisher');
        $referer = null;

        if ($provider->getToken()->hasReferer()) {
            $referer = $provider->getToken()->getReferer();
        }

        $node = $publisher->save(
            $request->request,
            $referer,
            $uuid
        );

        $provider->clearToken();

        return new JsonResponse(
            $this
                ->get('node.normalizer')
                ->normalize($node)
        );
    }
}
