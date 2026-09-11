<?php

namespace Tests;

use App\Services\AI\AIProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The real OpenAiProvider throws when OPENAI_API_KEY isn't
        // configured (it's inert-by-design until then). AI host listeners
        // queue off GameCompleted/RoundCompleted, which many unrelated
        // tests trigger incidentally; on the `sync` queue driver used in
        // tests, a queued listener's exception surfaces straight to the
        // caller. Bind a no-op default here so completing a game/round
        // doesn't blow up tests that aren't exercising the AI host feature;
        // tests that do exercise it (e.g. SendAiHostMessageTest) override
        // this binding themselves.
        $this->app->instance(AIProvider::class, new class implements AIProvider
        {
            public function respond(string $prompt): string
            {
                return '';
            }
        });
    }
}
