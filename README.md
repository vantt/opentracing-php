# OpenTracing API for PHP

[![Build Status](https://travis-ci.com/vantt/opentracing-php.svg?branch=master)](https://travis-ci.com/vantt/opentracing-php?branch=master)
[![OpenTracing Badge](https://img.shields.io/badge/OpenTracing-enabled-blue.svg)](http://opentracing.io)
[![Total Downloads](https://poser.pugx.org/vantt/opentracing-php/downloads)](https://packagist.org/packages/vantt/opentracing-php)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/github/license/vantt/opentracing-php.svg)](https://github.com/vantt/opentracing-php/blob/master/LICENSE)

PHP library for the OpenTracing's API.

This library extends the original *Tracer* Interface to add new method:  `builSpan(string $operationName): SpanBuildInterface`  

## Required Reading

In order to understand the library, one must first be familiar with the
[OpenTracing project](http://opentracing.io) and
[specification](http://opentracing.io/documentation/pages/spec.html) more specifically.

## Installation

OpenTracing-PHP can be installed via Composer:

```bash
composer require vantt/opentracing
```

## Usage

### Singleton initialization

The simplest starting point is to set the global tracer. As early as possible, do:

```php
use OpenTracing\GlobalTracer;

$tracer = GlobalTracer::set(new MyTracerImplementation());

```

### Using `SpanBuilder`

This library extends the original api to add a new method `buildSpan(operationName):SpanBuilderInterface`. 
When consuming this library one really only need to worry about the `buildSpan(operationName)` on the `$tracer` instance: `Tracer::buildSpan(operationName)`

With SpanBuilder, we can leverage the power of editor to do auto code completion for us with following APIs:

- `asChildOf($parentContext)` is an object of type `OpenTracing\SpanContext` or `OpenTracing\Span`.
- `withStartTimestamp(time())` is a float, int or `\DateTime` representing a timestamp with arbitrary precision.
- `withTag(key,val)` is an array with string keys and scalar values that represent OpenTracing tags.
- `ignoreActiveSpan(bool)`
- `finishSpanOnClose()` is a boolean that determines whether a span should be finished or not when the scope is closed.
- `addReference()`

Here are code snippets demonstrating some important use cases:

```php
$span = $tracer->buildSpan('my_span')
               ->asChildOf($parentContext)
               ->withTag('foo', 'bar')               
               ->withStartTimestamp(time())
               ->start();

$scope = $tracer->buildSpan('my_span')
                ->asChildOf($parentContext)
                ->withTag('foo', 'bar')               
                ->withStartTimestamp(time())
                ->startActive();
```

### Creating a Span given an existing Request

To start a new `Span`, you can use the `startSpan` method.

```php
use OpenTracing\Formats;
use OpenTracing\GlobalTracer;

...

// extract the span context
$spanContext = GlobalTracer::get()->extract(
    Formats\HTTP_HEADERS,
    getallheaders()
);

function doSomething() {
    ...

    // start a new span called 'my_span' and make it a child of the $spanContext
    $span = GlobalTracer::get()->buildSpan('my_operation_span_name')
                               ->start();
    ...
    
    // add some logs to the span
    $span->log([
        'event' => 'soft error',
        'type' => 'cache timeout',
        'waiter.millis' => 1500,
    ]);

    // finish the the span
    $span->finish();
}
```

### Starting a new trace by creating a "root span"

It's always possible to create a "root" `Span` with no parent or other causal reference.

```php
$span = $tracer->buildSpan('my_first_span')->start();
...
$span->finish();
```

### Active Spans and Scope Manager

For most use cases, it is recommended that you use the `Tracer::startActiveSpan` function for
creating new spans.

An example of a linear, two level deep span tree using active spans looks like
this in PHP code:
```php
// At dispatcher level
$scope = $tracer->buildSpan('request')->start();
...
$scope->close();
```
```php
// At controller level
$scope = $tracer->buildSpan('controller')->startActive();
...
$scope->close();
```

```php
// At RPC calls level
$scope = $tracer->buildSpan('http')->startActive();
file_get_contents('http://php.net');
$scope->close();
```
 
When using the `Tracer::startActiveSpan` function the underlying tracer uses an
abstraction called scope manager to keep track of the currently active span.

Starting an active span will always use the currently active span as a parent.
If no parent is available, then the newly created span is considered to be the
root span of the trace.

Unless you are using asynchronous code that tracks multiple spans at the same
time, such as when using cURL Multi Exec or MySQLi Polling it is recommended that you 
use `Tracer::startActiveSpan` everywhere in your application.

The currently active span gets automatically finished when you call `$scope->close()`
as you can see in the previous examples.

If you don't want a span to automatically close when `$scope->close()` is called
then you must specify `'finish_span_on_close'=> false,` in the `$options`
argument of `startActiveSpan`.

#### Creating a child span assigning parent manually

```php
$tracer = GlobalTracer::get();
$parent = $tracer->startSpan('parent');

$child = $tracer->buildSpan('child_operation')
                ->asChildOf($parent)
                ->start();
...

$child->finish();

...

$parent->finish();
```

#### Creating a child span using automatic active span management

Every new span will take the active span as parent and it will take its spot.

```php
$parent = GlobalTracer::get()->buildSpan('parent')->startActive();

...

/*
 * Since the parent span has been created by using startActiveSpan we don't need
 * to pass a reference for this child span
 */
$child = GlobalTracer::get()->buildSpan('my_second_span')->startActive();

...

$child->close();

...

$parent->close();
```

### Serializing to the wire

```php
use GuzzleHttp\Client;
use OpenTracing\Formats;

...

$tracer = GlobalTracer::get();

$spanContext = $tracer->extract(
    Formats\HTTP_HEADERS,
    getallheaders()
);

try {
    $span = $tracer->buildSpan('my_span')->asChildOf($spanContext)->start();

    $client = new Client;

    $headers = [];

    $tracer->inject(
        $span->getContext(),
        Formats\HTTP_HEADERS,
        $headers
    );

    $request = new \GuzzleHttp\Psr7\Request('GET', 'http://myservice', $headers);
    $client->send($request);
    ...

} catch (\Exception $e) {
    ...
}
...
```

### Deserializing from the wire

When using http header for context propagation you can use either the `Request` or the `$_SERVER`
variable:

```php
use OpenTracing\GlobalTracer;
use OpenTracing\Formats;

$tracer = GlobalTracer::get();
$spanContext = $tracer->extract(Formats\HTTP_HEADERS, getallheaders());
$tracer->buildSpan('my_span')->asChildOf($spanContext)->startActive();

```

### Flushing Spans

PHP as a request scoped language has no simple means to pass the collected spans
data to a background process without blocking the main request thread/process.
The OpenTracing API makes no assumptions about this, but for PHP that might
cause problems for Tracer implementations. This is why the PHP API contains a
`flush` method that allows to trigger a span sending out of process.

```php
use OpenTracing\GlobalTracer;

$application->run();

register_shutdown_function(function() {
    /* Flush the tracer to the backend */
    $tracer = GlobalTracer::get();
    $tracer->flush();
});
```

This is optional, tracers can decide to immediately send finished spans to a
backend. The flush call can be implemented as a NO-OP for these tracers.

### Using `StartSpanOptions`

This library is still compatible with the StartSpanOption.

Passing options to the pass can be done using either an array or the
SpanOptions wrapper object. The following keys are valid:

- `start_time` is a float, int or `\DateTime` representing a timestamp with arbitrary precision.
- `child_of` is an object of type `OpenTracing\SpanContext` or `OpenTracing\Span`.
- `references` is an array of `OpenTracing\Reference`.
- `tags` is an array with string keys and scalar values that represent OpenTracing tags.
- `finish_span_on_close` is a boolean that determines whether a span should be finished or not when the
scope is closed.

```php
$span = $tracer->startActiveSpan('my_span', [
    'child_of' => $spanContext,
    'tags' => ['foo' => 'bar'],
    'start_time' => time(),
]);
```

### Propagation Formats

The propagation formats should be implemented consistently across all tracers.
If you want to implement your own format, then don't reuse the existing constants.
Tracers will throw an exception if the requested format is not handled by them.

- `Tracer::FORMAT_TEXT_MAP` should represent the span context as a key value map. There is no
  assumption about the semantics where the context is coming from and sent to.

- `Tracer::FORMAT_HTTP_HEADERS` should represent the span context as HTTP header lines
  in an array list. For two context details "Span-Id" and "Trace-Id", the
  result would be `['Span-Id: abc123', 'Trace-Id: def456']`. This definition can be
  passed directly to `curl` and `file_get_contents`.

- `Tracer::FORMAT_BINARY` makes no assumptions about the data format other than it is
  proprietary and each Tracer can handle it as it wants.

## Mock implementation

OpenTracing PHP comes with a mock implementation, it has three purposes:

1. Helps to iron the API.
2. Works as a reference implementation.
3. Enhances vendor agnostic unit testing as it allows developers to inspect the tracing objects
in order to do assertions about them.

## Coding Style

OpenTracing PHP follows the [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
coding standard and the [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) autoloading standard.

## License

All the open source contributions are under the terms of the [Apache-2.0 License](https://opensource.org/licenses/Apache-2.0).
