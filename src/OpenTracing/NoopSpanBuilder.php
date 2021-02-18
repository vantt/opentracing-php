<?php

namespace OpenTracing;

class NoopSpanBuilder implements SpanBuilderInterface
{

    /**
     * @var Tracer
     */
    private $tracer;

    private $operationName;

    /**
     * NoopSpanBuilder constructor.
     *
     * @param string $operationName
     * @param        $tracer
     */
    public function __construct(string $operationName, $tracer)
    {
        $this->tracer        = $tracer;
        $this->operationName = $operationName;
    }

    public function asChildOf($parent): SpanBuilderInterface
    {
        return $this;
    }

    public function addReference(string $referenceType, $referencedContext): SpanBuilderInterface
    {
        return $this;
    }

    public function ignoreActiveSpan(): SpanBuilderInterface
    {
        return $this;
    }

    public function withTag(string $key, string $value): SpanBuilderInterface
    {
        return $this;
    }

    public function withStartTimestamp(int $microseconds): SpanBuilderInterface
    {
        return $this;
    }

    public function finishSpanOnClose(bool $val): SpanBuilderInterface
    {
        return $this;
    }
    public function start(): Span
    {
        return $this->tracer->startSpan($this->operationName, []);
    }

    public function startActive(): Scope
    {
        return $this->tracer->startActiveSpan($this->operationName, []);
    }
}
