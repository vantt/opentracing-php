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

    function addReference($referenceType, $referencedContext) {
        return $this;
    }

    function ignoreActiveSpan() {
        return $this;
    }

    function withTag($key, $value) {
        return $this;
    }

    function withStartTimestamp($microseconds) {
        return $this;
    }

    function finishSpanOnClose($val) {
    }

    function start() {
        return $this->tracer->startSpan($this->operationName, []);
    }

    function startActive() {
        return $this->tracer->startActiveSpan($this->operationName, []);
    }
}