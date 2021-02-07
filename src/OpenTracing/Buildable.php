<?php

namespace OpenTracing;


trait Buildable {

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
    public function buildSpan($operationName) {
        return new SpanBuilder($operationName, $this);
    }
}