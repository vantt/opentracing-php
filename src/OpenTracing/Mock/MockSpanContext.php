<?php

declare(strict_types = 1);

namespace OpenTracing\Mock;

use OpenTracing\SpanContext;
use ArrayIterator;

final class MockSpanContext implements SpanContext {
    /**
     * @var string
     */
    private $traceId;

    /**
     * @var string
     */
    private $spanId;

    /**
     * @var string
     */
    private $parentId = null;

    /**
     * @var bool
     */
    private $isSampled;

    /**
     * @var array
     */
    private $items;

    private function __construct(string $traceId, string $spanId, bool $isSampled, array $items) {
        $this->traceId   = $traceId;
        $this->spanId    = $spanId;
        $this->isSampled = $isSampled;
        $this->items     = $items;
    }

    public function setParentId(string $parentId) {
        $this->parentId = $parentId;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId(): ?string {
        return $this->parentId;
    }

    public static function create(string $traceId, string $spanId, bool $sampled = true, array $items = []): SpanContext {
        return new self($traceId, $spanId, $sampled, $items);
    }

    public static function createAsRoot(bool $sampled = true, array $items = []): SpanContext {
        $traceId = $spanId = self::nextId();
        return new self($traceId, $spanId, $sampled, $items);
    }

    public static function createAsChildOf(MockSpanContext $spanContext): SpanContext {
        $spanId = self::nextId();

        $context = new self($spanContext->getTraceId(), $spanId, $spanContext->isSampled, $spanContext->items);
        $context->setParentId($spanContext->getSpanId());

        return $context;
    }

    public function getTraceId(): string {
        return $this->traceId;
    }

    public function getSpanId(): string {
        return $this->spanId;
    }

    public function isSampled(): bool {
        return $this->isSampled;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string {
        return array_key_exists($key, $this->items) ? $this->items[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function withBaggageItem(string $key, string $value): SpanContext {
        return new self($this->traceId, $this->spanId, $this->isSampled, array_merge($this->items, [$key => $value]));
    }



    private static function nextId(): string {
        return (string)mt_rand(0, 99999);
    }

    /**
     * @return bool
     */
    public function isValid() {
        return $this->isTraceIdValid() && !empty($this->spanId);
    }


    /**
     * @return bool
     */
    public function isTraceIdValid() {
        return !empty($this->traceId);
    }
}
