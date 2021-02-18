<?php

declare(strict_types=1);

namespace OpenTracing;

use IteratorAggregate;

/**
 * SpanContext must be immutable in order to avoid complicated lifetime
 * issues around Span finish and references.
 *
 * Baggage items are key => value string pairs that apply to the given Span,
 * its SpanContext, and all Spans which directly or transitively reference
 * the local Span. That is, baggage items propagate in-band along with the
 * trace itself.
 */
interface SpanContext extends IteratorAggregate
{

    /**
     * Return the ID of the trace.
     *
     * Should be globally unique. Every span in a trace shares this ID.
     *
     * An empty String will be returned if the tracer does not support this functionality
     * (this is the case for no-op tracers, for example). null is an invalid return value.
     *
     * @return the trace ID for this context.
     */
    public function getTraceId(): string;

    /**
     * Return the ID of the associated Span.
     *
     * Should be unique within a trace. Each span within a trace contains a different ID.
     *
     * An empty String will be returned if the tracer does not support this functionality
     * (this is the case for no-op tracers, for example). null is an invalid return value.
     *
     * @return the Span ID for this context.
     */
    public function getSpanId(): string;

    /**
     * Return the ID of the associated ParentSpan.
     *
     * Should be unique within a trace. Each span within a trace contains a different ID.
     *
     * A NULL will be returned if this is a root span.
     *
     * @return the Span ID for this context.
     */
    public function getParentId(): ?string;

    /**
     * Returns the value of a baggage item based on its key. If there is no
     * value with such key it will return null.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getBaggageItem(string $key): ?string;

    /**
     * Creates a new SpanContext out of the existing one and the new key => value pair.
     *
     * @param string $key
     * @param string $value
     *
     * @return SpanContext
     */
    public function withBaggageItem(string $key, string $value): SpanContext;
}
