<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Assistants;

use Assistant\Application\Commands\UpdateAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\ValueObjects\Status;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\AssistantResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\FileSystem\CdnInterface;

#[Route(path: '/[uuid:id]', method: RequestMethod::PUT)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateAssistantRequestHandler extends AssistantsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private CdnInterface $cdn
    ) {
    }

    /**
     * @throws ValidationException
     * @throws UnableToWriteFile
     * @throws FilesystemException
     * @throws NotFoundException
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateAssistantCommand($request->getAttribute('id'));

        if (property_exists($payload, 'name')) {
            $cmd->setName($payload->name);
        }

        if (property_exists($payload, 'expertise')) {
            $cmd->setExpertise($payload->expertise ?: null);
        }

        if (property_exists($payload, 'description')) {
            $cmd->setDescription($payload->description ?: null);
        }

        if (property_exists($payload, 'instructions')) {
            $cmd->setInstructions($payload->instructions ?: null);
        }

        if ($request->getUploadedFiles()['file'] ?? null) {
            $file = $request->getUploadedFiles()['file'];
            $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

            $stream = $file->getStream();
            $stream->rewind();

            $name = Uuid::uuid4()->toString() . '.' . $ext;
            $this->cdn->write("/" . $name, $stream->getContents());

            $cmd->setAvatar($this->cdn->getUrl($name));
        }

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int) $payload->status);
        }

        try {
            /** @var AssistantEntity */
            $assistant = $this->dispatcher->dispatch($cmd);
        } catch (AssistantNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(
            new AssistantResource($assistant),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'string',
            'expertise' => 'string',
            'description' => 'string',
            'instructions' => 'string',
            'file' => 'uploaded_file',
            'status' => 'integer|in:' . implode(",", array_map(
                fn (Status $type) => $type->value,
                Status::cases()
            ))
        ]);
    }
}
