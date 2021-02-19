<?php

namespace OpenTracing;

use BadMethodCallException;

class SpanBuilder implements SpanBuilderInterface
{

    private $operationName;

    /**
     * @var Tracer
     */
    private $tracer             = null;

    private $startOptions = [
      'start_time'           => null,
      'finish_span_on_close' => true,
      'ignore_active_span'   => false,
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
            $this->startOptions['child_of'] = $parent;
        } elseif ($parent instanceof Span) {
            $this->startOptions['child_of'] = $parent->getContext();
        }

        return $this;
    }

    public function finishSpanOnClose(bool $val): SpanBuilderInterface
    {
        $this->startOptions['finish_span_on_close'] = $val;

        return $this;
    }

    public function withTag(string $key, string $value): SpanBuilderInterface
    {
        $this->startOptions['tags'][$key] = $value;

        return $this;
    }

    public function addReference(string $referenceType, $referencedContext): SpanBuilderInterface
    {
        $reference = null;

        if ($referencedContext instanceof SpanContext) {
            $reference = new Reference($referenceType, $referencedContext);
        } elseif ($referencedContext instanceof Span) {
            $reference = Reference::createForSpan($referenceType, $referencedContext);
        }

        if ($reference) {
            $this->startOptions['references'][] = $reference;
        }

        return $this;
    }

    public function ignoreActiveSpan(): SpanBuilderInterface
    {
        if (!empty($this->startOptions['child_of']) || !empty($this->startOptions['references'])) {
            throw new BadMethodCallException(
              'Usage of ignoreActiveSpan() after calling asChildOf() or addReference() is useless.'
            );
        }

        $this->startOptions['ignore_active_span'] = true;

        return $this;
    }

    public function withStartTimestamp(int $microseconds): SpanBuilderInterface
    {
        $this->startOptions['start_time'] = $microseconds;

        return $this;
    }

    public function start(): Span
    {
        //$this->setActiveSpan();

        return $this->tracer->startSpan($this->operationName, $this->getStartOptions());
    }

    public function startActive(): Scope
    {
        //$this->setActiveSpan();

        return $this->tracer->startActiveSpan($this->operationName, $this->getStartOptions());
    }

    public function getStartOptions(): StartSpanOptions {
        return StartSpanOptions::create($this->startOptions);
    }

//    private function setActiveSpan()
//    {
//        if ($this->startOptions['ignore_active_span']) {
//            return;
//        }
//
//        if (empty($this->startOptions['child_of']) && empty($this->startOptions['references'])) {
//            $this->asChildOf($this->tracer->getActiveSpan());
//        }
//    }
}
