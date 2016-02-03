<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class ExceptionListener
{
    protected $templating;
    protected $kernel;

    public function __construct(EngineInterface $templating, $kernel)
    {
        $this->templating = $templating;
        $this->kernel = $kernel;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // Exception object
        $exception = $event->getException();
        $message = $exception->getMessage();

        // new Response object
        $response = new Response();
        // set response content
        $response->setContent(
            // Create custom render view
            $this->templating->render('@App/exception.html.twig', array(
                'message' => $message,
                'exception' => $exception,
                ))
        );

        // HttpExceptionInterface is a special type of exception
        // that holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(500);
        }

        // set the new $response object to the $event
        $event->setResponse($response);
    }
}