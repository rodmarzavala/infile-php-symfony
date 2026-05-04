<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Testing;

use InfilePhp\Core\Contracts\DteContract;
use InfilePhp\Core\Enums\DteType;
use InfilePhp\Core\Http\CertificationResponse;
use InfilePhp\Core\Http\InfileClient;
use InfilePhp\Core\InfilePhp;

/**
 * Test double for the Infile SDK — mirrors Mail::fake() and Queue::fake() in API shape.
 *
 * @example
 *   FelFake::succeed();
 *   Invoice::create()->forFinalConsumer()->add(Item::product('Test')->unitPrice(10))->issue();
 *   FelFake::assertIssued(1);
 */
final class FelFake
{
    /** @var list<array{contract: DteContract, response: CertificationResponse}> */
    private array $issued = [];

    /** @var list<array{uuid: string, dteType: DteType, reason: string}> */
    private array $cancelled = [];

    private bool $shouldFail = false;

    private int $failTimes = 0;

    private int $callCount = 0;

    private bool $fallbackActivated = false;

    private int $retryCount = 0;

    private static ?self $instance = null;

    private function __construct()
    {
    }

    // -----------------------------------------------------------------------
    // Setup methods
    // -----------------------------------------------------------------------

    /**
     * Swap the real driver with a fake that always succeeds.
     */
    public static function succeed(): self
    {
        $instance = self::newInstance();
        $instance->shouldFail = false;

        return $instance;
    }

    /**
     * Swap the real driver with a fake that always fails.
     */
    public static function fail(): self
    {
        $instance = self::newInstance();
        $instance->shouldFail = true;

        return $instance;
    }

    /**
     * Fail the first N calls, then succeed.
     */
    public static function failTimes(int $times): self
    {
        $instance = self::newInstance();
        $instance->shouldFail = true;
        $instance->failTimes = $times;

        return $instance;
    }

    /**
     * Chain after failTimes() — semantically documents the eventual success.
     */
    public function thenSucceed(): self
    {
        return $this;
    }

    // -----------------------------------------------------------------------
    // Assertion methods
    // -----------------------------------------------------------------------

    /**
     * Assert a specific number of DTEs were issued.
     */
    public static function assertIssued(int $count): void
    {
        $instance = self::getInstance();
        $actual   = count($instance->issued);

        if ($actual !== $count) {
            throw new \AssertionError(
                "Expected {$count} DTE(s) to be issued, but {$actual} were issued."
            );
        }
    }

    /**
     * Assert that zero DTEs were issued.
     */
    public static function assertNothingIssued(): void
    {
        self::assertIssued(0);
    }

    /**
     * Assert the last issued DTE matches the given type.
     */
    public static function assertType(DteType $type): void
    {
        $instance = self::getInstance();
        $last     = end($instance->issued);

        if ($last === false) {
            throw new \AssertionError('No DTE has been issued yet.');
        }

        $actualType = $last['contract']->getType();
        if ($actualType !== $type) {
            throw new \AssertionError(
                "Expected last DTE to be of type {$type->value}, but it was {$actualType->value}."
            );
        }
    }

    /**
     * Assert the last issued DTE was for the given recipient NIT.
     */
    public static function assertRecipient(string $taxId): void
    {
        $instance = self::getInstance();

        $last = end($instance->issued);

        if ($last === false) {
            throw new \AssertionError('No DTE has been issued yet.');
        }

        $actualId = $last['contract']->getRecipient()?->getTaxId() ?? 'CF';
        if ($actualId !== $taxId) {
            throw new \AssertionError(
                "Expected last DTE recipient to have NIT/CUI {$taxId}, but it was {$actualId}."
            );
        }
    }

    /**
     * Assert that the CAFE fallback was activated.
     */
    public static function assertFallbackActivated(): void
    {
        $instance = self::getInstance();

        if (! $instance->fallbackActivated) {
            throw new \AssertionError('Expected the fallback (CAFE) to be activated, but it was not.');
        }
    }

    /**
     * Assert the SDK retried exactly N times.
     */
    public static function assertRetries(int $count): void
    {
        $instance = self::getInstance();

        if ($instance->retryCount !== $count) {
            throw new \AssertionError(
                "Expected {$count} retry attempt(s), but {$instance->retryCount} occurred."
            );
        }
    }

    /**
     * Assert exactly N DTEs were cancelled.
     */
    public static function assertCancelled(int $count): void
    {
        $instance = self::getInstance();
        $actual   = count($instance->cancelled);

        if ($actual !== $count) {
            throw new \AssertionError(
                "Expected {$count} DTE(s) to be cancelled, but {$actual} were cancelled."
            );
        }
    }

    // -----------------------------------------------------------------------
    // Internal fake driver
    // -----------------------------------------------------------------------

    /**
     * Simulate a certify call — used by the fake InfileClient binding.
     *
     * @throws \InfilePhp\Core\Exceptions\InfileCertificationException
     */
    public function handleCertify(DteContract $dte): CertificationResponse
    {
        $this->callCount++;

        $shouldFailThisCall = $this->shouldFail && ($this->failTimes === 0 || $this->callCount <= $this->failTimes);

        if ($shouldFailThisCall) {
            throw new \InfilePhp\Core\Exceptions\InfileCertificationException(
                message: 'FelFake: simulated certification failure.',
                statusCode: 500,
                infileCode: 'FAKE_ERROR',
            );
        }

        $response = new CertificationResponse(
            uuid: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            serie: 'FAKE',
            numero: (string) $this->callCount,
            xmlCertified: base64_encode('<DTE certified="true"/>'),
            remainingCreditsVal: 2_000,
        );

        $this->issued[] = [
            'contract' => $dte,
            'response' => $response,
        ];

        return $response;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private static function newInstance(): self
    {
        $instance       = new self();
        self::$instance = $instance;

        // Ensure config is loaded before we mock the client
        if (InfilePhp::config() === null) {
            // This should not happen if FelServiceProvider booted
            throw new \RuntimeException('InfilePhp is not configured.');
        }

        // Create a Mockery mock of InfileClient
        $mock = \Mockery::mock(InfileClient::class);
        $mock->shouldReceive('certify')->andReturnUsing(function ($dte, $idempotencyKey = null) use ($instance) {
            return $instance->handleCertify($dte);
        });
        $mock->shouldReceive('cancel')->andReturnUsing(function ($uuid, $dteType, $reason) use ($instance) {
            $instance->cancelled[] = ['uuid' => $uuid, 'dteType' => $dteType, 'reason' => $reason];
        });
        $mock->shouldReceive('ping')->andReturn(200);

        // Swap the real InfileClient binding with this fake
        InfilePhp::swapClient($mock);

        // TODO: In Symfony, we might need a different way to swap the container service if users are injecting InfileClient directly, but typically they inject InfileService.

        return $instance;
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException(
                'FelFake has not been initialized. Call FelFake::succeed() or FelFake::fail() first.'
            );
        }

        return self::$instance;
    }
}
