<?php


namespace OpenTracing;


class NoopSpanBuilder implements SpanBuilderInterface {

    /**
     * @var Tracer
     */
    private $tracer;

    private $operationName;

    /**
     * NoopSpanBuilder constructor.
     */
    public function __construct(string $operationName, $tracer) {
        $this->tracer        = $tracer;
        $this->operationName = $operationName;
    }

    function asChildOf($parent) {
        return $this;
    }

    function addReference(string $referenceType, \OpenTracing\SpanContext $referencedContext): SpanBuilderInterface {
        return $this;
    }

    function ignoreActiveSpan(): SpanBuilderInterface {
        return $this;
    }

    function withTag(string $key, string $value): SpanBuilderInterface {
        return $this;
    }

    function withStartTimestamp(int $microseconds) {
        return $this;
    }

    function start(): Span {
        return $this->tracer->startSpan($this->operationName, []);
    }

    function startActive(): Scope {
        return $this->tracer->startActiveSpan($this->operationName, []);
    }

}