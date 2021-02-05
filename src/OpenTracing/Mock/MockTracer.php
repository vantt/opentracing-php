<?php

declare(strict_types = 1);

namespace OpenTracing\Mock;

use OpenTracing\InvalidReferenceArgumentException;
use OpenTracing\Reference;
use OpenTracing\SpanBuilder;
use OpenTracing\SpanBuilderInterface;
use OpenTracing\UnsupportedFormatException;
use OpenTracing\Scope;
use OpenTracing\ScopeManager;
use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;

final class MockTracer implements Tracer {
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
    public function startActiveSpan(string $operationName, $options = []): Scope {
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
    public function startSpan(string $operationName, $options = []): Span {
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
                throw InvalidReferenceArgumentException::forInvalidContext($parentSpanContext);
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
    public function inject(SpanContext $spanContext, string $format, &$carrier): void {
        if (!array_key_exists($format, $this->injectors)) {
            throw UnsupportedFormatException::forFormat($format);
        }

        $this->injectors[$format]($spanContext, $carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function extract(string $format, $carrier): ?SpanContext {
        if (!array_key_exists($format, $this->extractors)) {
            throw UnsupportedFormatException::forFormat($format);
        }

        return $this->extractors[$format]($carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void {
        $this->spans = [];
    }

    /**
     * @return array|MockSpan[]
     */
    public function getSpans(): array {
        return $this->spans;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeManager(): ScopeManager {
        return $this->scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSpan(): ?Span {
        if (null !== ($activeScope = $this->scopeManager->getActive())) {
            return $activeScope->getSpan();
        }

        return null;
    }

    /**
     * Return a new SpanBuilder for a Span with the given `operationName`.
     *
     * <p>You can override the operationName later via {@link Span#setOperationName(String)}.
     *
     * <p>A contrived example:
     * <pre><code>
     *   Tracer tracer = ...
     *
     *   // Note: if there is a `tracer.activeSpan()` instance, it will be used as the target
     *   // of an implicit CHILD_OF Reference when `start()` is invoked,
     *   // unless another Span reference is explicitly provided to the builder.
     *   Span span = tracer.buildSpan("HandleHTTPRequest")
     *                     .asChildOf(rpcSpanContext)  // an explicit parent
     *                     .withTag("user_agent", req.UserAgent)
     *                     .withTag("lucky_number", 42)
     *                     .start();
     *   span.setTag("...", "...");
     *
     *   // It is possible to set the Span as the active instance for the current context
     *   // (usually a thread).
     *   try (Scope scope = tracer.activateSpan(span)) {
     *      ...
     *   }
     * </code></pre>
     *
     * @param string $operationName
     *
     * @return SpanBuilderInterface
     */
    public function buildSpan(string $operationName): SpanBuilderInterface {
        return new SpanBuilder($operationName, $this);
    }

    private function getParentSpanContext(StartSpanOptions $options): ?SpanContext {
        $references = $options->getReferences();
        $parentSpan = null;

        foreach ($references as $ref) {
            $parentSpan = $ref->getSpanContext();
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
