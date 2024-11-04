<?php

namespace Meetings\Errors;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use StudipPlugin;
use Throwable;
use Slim\Middleware\ErrorMiddleware as SlimErrorMiddleware;

class ErrorMiddleware extends SlimErrorMiddleware
{
    use PreparesJsonapiResponse;

    /**
     * @var StudipPlugin plugin
     */
    protected $plugin;

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
        ?LoggerInterface $logger = null,
        StudipPlugin $plugin
    ) {
        $this->callableResolver = $callableResolver;
        $this->responseFactory = $responseFactory;
        $this->displayErrorDetails = $displayErrorDetails;
        $this->logErrors = $logErrors;
        $this->logErrorDetails = $logErrorDetails;
        $this->logger = $logger;
        $this->plugin = $plugin;
    }

    public function handleException(ServerRequestInterface $request, Throwable $exception): \Psr\Http\Message\ResponseInterface
    {
        $message_format = $this->plugin->getPluginName() . ' - Slim Application Error: %s';

        $accepts = $request->getHeaderLine('accept');
        $accepts = array_map('trim', explode(',', $accepts));

        $is_catchable = $exception->getCode() >= 400 && $exception->getCode() < 600;
        $is_accepted = is_array($accepts) && in_array('application/json', $accepts);


        if ($is_catchable && $is_accepted) {
            return $this->prepareResponseMessage(
                app(ResponseFactoryInterface::class)->createResponse($exception->getCode()),
                new Error(sprintf($message_format, $exception->getMessage()), $exception->getCode())
            );
        }

        return parent::handleException($request, $exception);
    }
}
