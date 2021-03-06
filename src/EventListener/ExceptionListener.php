<?php

namespace App\EventListener;

use Anyx\LoginGateBundle\Exception\BruteForceAttemptException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionListener
{
    private $serializer;
    private $urlGenerator;

    public function __construct(SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator)
    {
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // we overwrite every kernel exception, to display only one standard message to the user
        $json = $this->serializer->serialize([
            'message' => 'Bad request. Check your parameters, reminder that documention is here : '.
            $this->urlGenerator->generate('app_documentation', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ], 'json');
        $response = new Response($json, 400, ['Content-Type' => 'application/json']);

        // except this type of brute force exception, where we set a customized message
        if (($exception->getPrevious()) instanceof BruteForceAttemptException) {
            $json = $this->serializer->serialize(['message' => 'Too many login attempts. Please retry in 10 minutes.'], 'json');
            $response->setStatusCode(401)->setContent($json);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
