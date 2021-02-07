<?php

namespace OpenTracing\Tests;

use OpenTracing\Mock\MockTracer;
use OpenTracing\StartSpanOptions;
use PHPUnit\Framework\TestCase;


/**
 * @covers StartSpanOptions
 */
final class SpanBuilderTest extends TestCase {
    const OPERATION_NAME = 'test_operation';

    public function test_StartActive_Success() {
        $tracer = new MockTracer();
        $scope  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->startActive();

        $this->assertEquals($scope->getSpan(), $tracer->getActiveSpan());
    }

    public function test_Start_Success() {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $activeSpan = $tracer->getActiveSpan();

        $this->assertNotNull($span);
        $this->assertNull($activeSpan);
    }

    public function test__IgnoreActiveSpan__Success() {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $this->assertNull($span->getContext()->getParentId());
    }

    public function test_asChildOf_Success() {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->asChildOf($span1->getContext())
                        ->start();

        $span3 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->asChildOf($span2->getContext())
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span2->getContext()->getTraceId(), $span3->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getTraceId(), $span3->getContext()->getTraceId());

        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
        $this->assertEquals($span2->getContext()->getSpanId(), $span3->getContext()->getParentId());
    }


    public function test__RUN_withTag__Success() {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->withTag('tag1', 'value1')
                         ->withTag('tag2', 'value2')
                         ->start();

        $this->assertEquals(['tag2' => 'value2', 'tag1' => 'value1'], $span->getTags());
    }

    public function test__withoutRUN_withTag__Success() {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)->start();

        $this->assertEquals([], $span->getTags());
    }

    public function test__RUN__withStartTimestamp__Success() {
        $tracer    = new MockTracer();
        $startTime = time();
        $span      = $tracer->buildSpan(self::OPERATION_NAME)
                            ->withStartTimestamp($startTime)
                            ->start();

        $this->assertEquals($startTime, $span->getStartTime());
    }

    public function test__withoutRUN__withStartTimestamp__StartTimeAutoSet() {
        $tracer = new MockTracer();
        $now    = time();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $this->assertEquals($now, $span->getStartTime());
    }
}