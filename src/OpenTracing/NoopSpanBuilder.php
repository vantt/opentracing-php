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
     */
    public function __construct(string $operationName, $tracer)
    {
        $this->tracer        = $tracer;
        $this->operationName = $operationName;
    }

    public function asChildOf($parent)
    {
        return $this;
    }

    public function addReference($referenceType, $referencedContext)
    {
        return $this;
    }

    public function ignoreActiveSpan()
    {
        return $this;
    }

    public function withTag($key, $value)
    {
        return $this;
    }

    public function withStartTimestamp($microseconds)
    {
        return $this;
    }

    public function finishSpanOnClose($val)
    {
    }

    public function start()
    {
        return $this->tracer->startSpan($this->operationName, []);
    }

    public function startActive()
    {
        return $this->tracer->startActiveSpan($this->operationName, []);
    }
}
