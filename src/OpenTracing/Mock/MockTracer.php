<?php

namespace OpenTracing\Mock;

use OpenTracing\Buildable;
use OpenTracing\BuildableInterface;
use OpenTracing\Exceptions\UnsupportedFormat;
use OpenTracing\Reference;
use OpenTracing\ScopeManager;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;
use OpenTracing\SpanContext;
use OpenTracing\Exceptions\InvalidReferenceArgument;

final class MockTracer implements Tracer, BuildableInterface {

    use Buildable;

    /**
     * @var array|MockSpan[]
     */
    private $spans = [];

    /**
     * @var array|callable[]
     */
    private $injectors;

    /**
     * @var array|callable[]
     */
    private $extractors;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    public function __construct(array $injectors = [], array $extractors = []) {
        $this->injectors    = $injectors;
        $this->extractors   = $extractors;
        $this->scopeManager = new MockScopeManager();
    }

    /**
     * {@inheritdoc}
     */
    public function startActiveSpan($operationName, $options = []) {
        if (!($options instanceof StartSpanOptions)) {
            $options = StartSpanOptions::create($options);
        }

        $parentSpan = $this->getParentSpanContext($options);
        if ($parentSpan === null) {
            if ($parentSpan = $this->getActiveSpan()) {
                $options = $options->withParent($parentSpan->getContext());
            }
        }

        $span = $this->startSpan($operationName, $options);

        return $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());
    }

    /**
     * {@inheritdoc}
     */
    public function startSpan($operationName, $options = []) {
        if (!($options instanceof StartSpanOptions)) {
            $options = StartSpanOptions::create($options);
        }

        $spanContext       = null;
        $parentSpanContext = $this->getParentSpanContext($options);

        if (null === $parentSpanContext || !$parentSpanContext->isValid()) {
            $spanContext = MockSpanContext::createAsRoot();
        }
        else {
            if (!$parentSpanContext instanceof MockSpanContext) {
                throw InvalidReferenceArgument::forInvalidContext($parentSpanContext);
            }
            $spanContext = MockSpanContext::createAsChildOf($parentSpanContext);
        }

        $span = new MockSpan($operationName, $spanContext, $options->getStartTime());

        foreach ($options->getTags() as $key => $value) {
            $span->setTag($key, $value);
        }

        $this->spans[] = $span;

        return $span;
    }

    /**
     * {@inheritdoc}
     */
    public function inject(SpanContext $spanContext, $format, &$carrier) {
        if (!array_key_exists($format, $this->injectors)) {
            throw UnsupportedFormat::forFormat($format);
        }

        call_user_func($this->injectors[$format], $spanContext, $carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function extract($format, $carrier) {
        if (!array_key_exists($format, $this->extractors)) {
            throw UnsupportedFormat::forFormat($format);
        }

        return call_user_func($this->extractors[$format], $carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function flush() {
        $this->spans = [];
    }

    /**
     * @return array|MockSpan[]
     */
    public function getSpans() {
        return $this->spans;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeManager() {
        return $this->scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSpan() {
        if (null !== ($activeScope = $this->scopeManager->getActive())) {
            return $activeScope->getSpan();
        }

        return null;
    }

    private function getParentSpanContext($startSpanOptions) {
        $references = $startSpanOptions->getReferences();
        $parentSpan = null;

        foreach ($references as $ref) {
            $parentSpan = $ref->getContext();
            if ($ref->isType(Reference::CHILD_OF)) {
                return $parentSpan;
            }
        }

        if ($parentSpan) {
            if (($parentSpan->isValid()
                 || (!$parentSpan->isTraceIdValid() && $parentSpan->debugId)
                 || count($parentSpan->baggage) > 0)
            ) {
                return $parentSpan;
            }
        }

        return null;
    }
}
