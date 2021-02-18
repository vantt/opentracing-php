<?php

namespace OpenTracing;

use OpenTracing\Reference;
use OpenTracing\Scope;
use OpenTracing\SpanContext;
use OpenTracing\Span;
use OpenTracing\Tracer;

class SpanBuilder implements SpanBuilderInterface
{

    private $operationName;

    /**
     * @var Tracer
     */
    private $tracer             = null;
    private $ignoringActiveSpan = false;

    private $starOptions = [
      'start_time'           => null,
      'finish_span_on_close' => true,
      'tags'                 => [],
      'references'           => [],
    ];

    public function __construct(string $operationName, $tracer)
    {
        $this->operationName = $operationName;
        $this->tracer        = $tracer;
    }


    public function asChildOf($parent): SpanBuilderInterface
    {
        if ($parent instanceof SpanContext) {
            $this->starOptions['child_of'] = $parent;
        } elseif ($parent instanceof Span) {
            $this->starOptions['child_of'] = $parent->getContext();
        }

        return $this;
    }

    public function finishSpanOnClose(bool $val): SpanBuilderInterface
    {
        $this->starOptions['finish_span_on_close'] = $val;
        return $this;
    }

    public function withTag(string $key, string $value): SpanBuilderInterface
    {
        $this->starOptions['tags'][$key] = $value;

        return $this;
    }

    public function addReference(string $referenceType, SpanContext $referencedContext): SpanBuilderInterface
    {
        if ($referencedContext != null) {
            $this->starOptions['references'][] = Reference::createForSpan($referenceType, $referencedContext);
        }
        return $this;
    }

    public function ignoreActiveSpan(): SpanBuilderInterface
    {
        $this->ignoringActiveSpan = true;

        return $this;
    }

    public function withStartTimestamp(int $microseconds): SpanBuilderInterface
    {
        $this->starOptions['start_time'] = $microseconds;

        return $this;
    }

    public function start(): Span
    {
        $this->verifyActiveSpan();
        return $this->tracer->startSpan($this->operationName, $this->starOptions);
    }

    public function startActive(): Scope
    {
        $this->verifyActiveSpan();
        return $this->tracer->startActiveSpan($this->operationName, $this->starOptions);
    }

    private function verifyActiveSpan()
    {
        if (empty($this->starOptions['child_of']) && !$this->ignoringActiveSpan) {
            $this->asChildOf($this->tracer->getActiveSpan());
        }
    }
}
