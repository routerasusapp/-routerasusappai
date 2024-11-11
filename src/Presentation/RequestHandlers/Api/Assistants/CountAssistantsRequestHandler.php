<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Assistants;

use Assistant\Application\Commands\CountAssistantsCommand;
use Assistant\Domain\ValueObjects\Status;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountAssistantsRequestHandler extends AssistantApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = (object) $request->getQueryParams();

        $cmd = new CountAssistantsCommand();
        $cmd->status = Status::from(1);

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        /** @var int */
        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
