<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\OpenAi;

use Ai\Domain\Completion\CodeCompletionServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Token;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use Generator;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use Override;

class CodeCompletionService implements CodeCompletionServiceInterface
{
    private array $models = [
        'gpt-4o',
        'gpt-4-turbo',
        'gpt-4-turbo-preview',
        'gpt-4',
        'gpt-3.5-turbo',
        'gpt-3.5-turbo-instruct'
    ];

    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function generateCodeCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = [],
    ): Generator {
        try {
            if ($model->value == 'gpt-3.5-turbo-instruct') {
                return $this->generateInstructedCompletion($model, $prompt, $language, $params);
            }
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }

        try {
            return $this->generateChatCompletion($model, $prompt, $language, $params);
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @return Generator<int,Token,null,Count>
     * @throws ErrorException
     * @throws RuntimeException
     */
    private function generateInstructedCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = [],
    ): Generator {
        $prompt = $params['prompt'] ?? '';

        $resp = $this->client->completions()->createStreamed([
            'model' => $model->value,
            'prompt' => "You're $language programming language expert. $prompt",
            'temperature' => (int)($params['temperature'] ?? 1),
        ]);

        $inputTokensCount = $this->tokenizer->count($prompt);
        $outputTokensCount = 0;

        foreach ($resp as $item) {
            $content = $item->choices[0]->text;

            if ($content) {
                $outputTokensCount++;
                yield new Token($content);
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new Count($inputCost->value + $outputCost->value);
    }

    /**
     * @return Generator<int,Token,null,Count>
     * @throws RuntimeException
     * @throws ErrorException
     */
    private function generateChatCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = [],
    ): Generator {
        $resp = $this->client->chat()->createStreamed([
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You're $language programming language expert."
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => (int)($params['temperature'] ?? 1),
        ]);

        $inputTokensCount = $this->tokenizer->count($prompt);
        $outputTokensCount = 0;

        foreach ($resp as $item) {
            $content = $item->choices[0]->delta->content;

            if ($content) {
                $outputTokensCount++;
                yield new Token($content);
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new Count($inputCost->value + $outputCost->value);
    }
}
