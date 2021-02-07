<?php

namespace OpenTracing;

use OpenTracing\Scope;
use OpenTracing\Span;
use OpenTracing\SpanContext;

interface SpanBuilderInterface {

    /**
     * @param SpanContext|Span $parent
     *
     * @return SpanBuilderInterface
     */
    function asChildOf($parent): SpanBuilderInterface;

    /**
     * Add a reference from the Span being built to a distinct (usually parent) Span. May be called multiple times
     * to represent multiple such References.
     *
     * <p>
     * If
     * <ul>
     * <li>the {@link Tracer}'s {@link ScopeManager#activeSpan()} is not null, and
     * <li>no <b>explicit</b> references are added via {@link SpanBuilder#addReference}, and
     * <li>{@link SpanBuilder#ignoreActiveSpan()} is not invoked,
     * </ul>
     * ... then an inferred {@link References#CHILD_OF} reference is created to the
     * {@link ScopeManager#activeSpan()} {@link SpanContext} when either {@link SpanBuilder#startActive(boolean)} or
     * {@link SpanBuilder#start} is invoked.
     *
     * @param referenceType the reference type, typically one of the constants defined in References
     * @param referencedContext the SpanContext being referenced; e.g., for a References.CHILD_OF referenceType, the
     *                          referencedContext is the parent. If referencedContext==null, the call to
     *                          {@link #addReference} is a noop.
     *
     * @param string      $referenceType
     * @param SpanContext $referencedContext
     *
     * @return SpanBuilderInterface
     *
     */
    function addReference(string $referenceType, SpanContext $referencedContext): SpanBuilderInterface;

    /**
     * Do not create an implicit {@link References#CHILD_OF} reference to the {@link ScopeManager#activeSpan()}).
     * @return SpanBuilderInterface
     */
    function ignoreActiveSpan(): SpanBuilderInterface;

    /**
     * Same as {@link Span#setTag(String, String)}, but for the span being built.
     *
     * @param string $key
     * @param string $value
     *
     * @return @return SpanBuilderInterface
     */
    function withTag(string $key, string $value): SpanBuilderInterface;


    /**
     * Specify a timestamp of when the Span was started, represented in microseconds since epoch.
     *
     * @param int $microseconds
     *
     * @return SpanBuilderInterface
     */
    function withStartTimestamp(int $microseconds): SpanBuilderInterface;

    /**
     * Returns a newly-started {@link Span}.
     *
     * <p>
     * If
     * <ul>
     * <li>the {@link Tracer}'s {@link ScopeManager#activeSpan()} is not null, and
     * <li>no <b>explicit</b> references are added via {@link SpanBuilder#addReference}, and
     * <li>{@link SpanBuilder#ignoreActiveSpan()} is not invoked,
     * </ul>
     * ... then an inferred {@link References#CHILD_OF} reference is created to the
     * {@link ScopeManager#activeSpan()}'s {@link SpanContext} when either
     * {@link SpanBuilder#start()} or {@link SpanBuilder#startActive} is invoked.
     * @return Span the newly-started Span instance, which has *not* been automatically registered
     *         via the {@link ScopeManager}
     */
    function start(): Span;

    function startActive(): Scope;

    function finishSpanOnClose(bool $val): SpanBuilderInterface;
}