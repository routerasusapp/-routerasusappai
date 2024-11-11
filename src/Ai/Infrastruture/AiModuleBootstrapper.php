<?php

declare(strict_types=1);

namespace Ai\Infrastruture;

use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Infrastruture\Repositories\DoctrineOrm\LibraryItemRepository;
use Ai\Infrastruture\Services\AiServiceFactory;
use Ai\Infrastruture\Services\OpenAi as OpenAiServices;
use Ai\Infrastruture\Services\ElevenLabs as ElevenLabsServices;
use Ai\Infrastruture\Services\StabilityAi as StabilityAiServices;
use Ai\Infrastruture\Services\Clipdrop as ClipdropServices;
use Ai\Infrastruture\Services\Google as GoogleServices;
use Ai\Infrastruture\Services\Anthropic as AnthropicServices;
use Ai\Infrastruture\Services\Azure as AzureServices;
use Application;
use Easy\Container\Attributes\Inject;
use Http\Discovery\Exception\NotFoundException;
use OpenAI;
use OpenAI\Client;
use Psr\Http\Client\ClientInterface;
use Shared\Infrastructure\BootstrapperInterface;

class AiModuleBootstrapper implements BootstrapperInterface
{
    /**
     * @param Application $app 
     * @return void 
     */
    public function __construct(
        private Application $app,
        private AiServiceFactory $factory,
        private ClientInterface $httpClient,

        #[Inject('option.openai.api_secret_key')]
        private ?string $openAiApiKey = null
    ) {
    }

    /** @inheritDoc */
    public function bootstrap(): void
    {
        $this->setupAiServiceFactory();
        $this->setupOpenAIClient();
    }

    /** @return void  */
    private function setupAiServiceFactory(): void
    {
        $this->app->set(
            LibraryItemRepositoryInterface::class,
            LibraryItemRepository::class
        );

        $this->app->set(
            AiServiceFactoryInterface::class,
            $this->factory
        );

        $this->factory
            ->register(OpenAiServices\CompletionService::class)
            ->register(OpenAiServices\TitleGeneratorService::class)
            ->register(OpenAiServices\CodeCompletionService::class)
            ->register(OpenAiServices\ImageService::class)
            ->register(OpenAiServices\TranscriptionService::class)
            ->register(OpenAiServices\SpeechService::class)
            ->register(OpenAiServices\MessageService::class)
            ->register(ElevenLabsServices\SpeechService::class)
            ->register(GoogleServices\SpeechService::class)
            ->register(StabilityAiServices\ImageGeneratorService::class)
            ->register(ClipdropServices\ImageGeneratorService::class)
            ->register(AnthropicServices\CompletionService::class)
            ->register(AnthropicServices\CodeCompletionService::class)
            ->register(AnthropicServices\TitleGeneratorService::class)
            ->register(AnthropicServices\MessageService::class)
            ->register(AzureServices\SpeechService::class);
    }

    /**
     * @return void 
     * @throws NotFoundException 
     */
    private function setupOpenAIClient(): void
    {
        if (!$this->openAiApiKey) {
            return;
        }

        $client = OpenAI::factory()
            ->withApiKey($this->openAiApiKey)
            ->withHttpClient($this->httpClient)
            ->make();

        $this->app->set(Client::class, $client);
    }
}
