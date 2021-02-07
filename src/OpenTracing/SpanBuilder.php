<?php

namespace OpenTracing;

use OpenTracing\Reference;
use OpenTracing\Scope;
use OpenTracing\SpanContext;
use OpenTracing\Span;
use OpenTracing\Tracer;

class SpanBuilder implements SpanBuilderInterface {

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

    public function __construct($operationName, $tracer) {
        $this->operationName = $operationName;
        $this->tracer        = $tracer;
    }


    public function asChildOf($parent) {
        if ($parent instanceof SpanContext) {
            $this->starOptions['child_of'] = $parent;
        }
        elseif ($parent instanceof Span) {
            $this->starOptions['child_of'] = $parent->getContext();
        }

        return $this;
    }

    function finishSpanOnClose($val) {
        $this->starOptions['finish_span_on_close'] = $val;
    }

    public function withTag($key, $value) {
        $this->starOptions['tags'][$key] = $value;

        return $this;
    }

    public function addReference($referenceType, $referencedContext) {
        if ($referencedContext != null) {
            $this->starOptions['references'][] = Reference::create($referenceType, $referencedContext);
        }

        return $this;
    }

    public function ignoreActiveSpan() {
        $this->ignoringActiveSpan = true;

        return $this;
    }

    public function withStartTimestamp($microseconds) {
        $this->starOptions['start_time'] = $microseconds;

        return $this;
    }

    public function start() {
        $this->verifyActiveSpan();

        return $this->tracer->startSpan($this->operationName, $this->starOptions);
    }

    public function startActive() {
        $this->verifyActiveSpan();

        return $this->tracer->startActiveSpan($this->operationName, $this->starOptions);
    }

    private function verifyActiveSpan() {
        if (empty($this->starOptions['child_of']) && !$this->ignoringActiveSpan) {
            $this->asChildOf($this->tracer->getActiveSpan());
        }
    }

}