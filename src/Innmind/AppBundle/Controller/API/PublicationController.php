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

        $labels = $this->get('label_guesser')->guess($request->request);
        $node = $this
            ->get('graph')
            ->createNode(
                $labels,
                $request->request->all()
            );

        if ($provider->getToken()->hasReferer()) {
            $referer = $this
                ->get('graph')
                ->getNodeByProperty(
                    'uri',
                    $provider->getToken()->getReferer()
                );

            $relation = $this
                ->get('graph')
                ->createRelation(
                    $referer,
                    $node
                );
            $relation
                ->setType('REFER')
                ->setProperty('weight', 1)
                ->save();
        }

        $provider->clearToken();

        return new JsonResponse(
            $this
                ->get('node_normalizer')
                ->normalize($node)
        );
    }
}
