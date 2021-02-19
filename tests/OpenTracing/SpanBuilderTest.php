<?php

declare(strict_types = 1);

namespace OpenTracing\Tests;

use BadMethodCallException;
use Mockery;
use OpenTracing\Mock\MockTracer;
use OpenTracing\Reference;
use OpenTracing\Span;
use OpenTracing\SpanBuilder;
use OpenTracing\SpanBuilderInterface;
use PHPUnit\Framework\TestCase;

final class SpanBuilderTest extends TestCase
{
    private const OPERATION_NAME = 'test_operation';

    public function test__StartActive__Success()
    {
        $tracer = new MockTracer();
        $scope  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->startActive();

        $this->assertEquals($scope->getSpan(), $tracer->getActiveSpan());
    }

    public function test_buildSpan_Success() {
        $tracer = new MockTracer();
        $builder  = $tracer->buildSpan(self::OPERATION_NAME);

        $this->assertInstanceOf(SpanBuilderInterface::class, $builder);
        $this->assertInstanceOf(SpanBuilder::class, $builder);
    }

    public function test_Start_Success()
    {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $activeSpan = $tracer->getActiveSpan();

        $this->assertNotNull($span);
        $this->assertNull($activeSpan);
    }

    public function test__asChildOf_UsingSpan__Success()
    {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->asChildOf($span1)
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
    }

    public function test__asChildOf_UsingSpanContext__Success()
    {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->asChildOf($span1->getContext())
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
    }

    public function test__asChildOf_Nested__Success()
    {
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
        $this->assertEquals($span1->getContext()->getTraceId(), $span3->getContext()->getTraceId());

        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
        $this->assertEquals($span2->getContext()->getSpanId(), $span3->getContext()->getParentId());
    }

    public function test__addReference_UsingSpan__Success()
    {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->addReference(Reference::CHILD_OF, $span1)
                        ->addReference(Reference::FOLLOWS_FROM, $span1)
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
    }

    public function test__addReference_UsingSpanContext__Success()
    {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->addReference(Reference::CHILD_OF, $span1->getContext())
                        ->addReference(Reference::FOLLOWS_FROM, $span1->getContext())
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
    }

    public function test__addReference_CHILDOF__Success()
    {
        $tracer = new MockTracer();
        $span1  = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $span2 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->addReference(Reference::CHILD_OF, $span1)
                        ->start();

        $span3 = $tracer->buildSpan(self::OPERATION_NAME)
                        ->addReference(Reference::CHILD_OF, $span2)
                        ->start();

        $this->assertEquals($span1->getContext()->getTraceId(), $span2->getContext()->getTraceId());
        $this->assertEquals($span1->getContext()->getTraceId(), $span3->getContext()->getTraceId());

        $this->assertEquals($span1->getContext()->getSpanId(), $span2->getContext()->getParentId());
        $this->assertEquals($span2->getContext()->getSpanId(), $span3->getContext()->getParentId());
    }

    public function test__addReference__WithTwoChildReferences__FirstOneWillBeUsed()
    {
        $tracer   = new MockTracer();
        $rootSpan = $tracer->buildSpan(self::OPERATION_NAME)
                           ->start();

        $otherRootSpan = $tracer->buildSpan(self::OPERATION_NAME)
                                ->start();

        $childSpan = $tracer->buildSpan(self::OPERATION_NAME)
                            ->addReference(Reference::CHILD_OF, $rootSpan)
                            ->addReference(Reference::CHILD_OF, $otherRootSpan)
                            ->start();

        $this->assertEquals($childSpan->getContext()->getTraceId(), $rootSpan->getContext()->getTraceId());
        $this->assertEquals($rootSpan->getContext()->getSpanId(), $childSpan->getContext()->getParentId());
    }

    public function test__addReference__WithDifferentReferenceTypes__FirstChildReferenceWillBeUsed()
    {
        $tracer = new MockTracer();
        $rootA  = $tracer->buildSpan('root-a')
                         ->start();

        $rootB = $tracer->buildSpan('root-b')
                        ->start();

        $rootC = $tracer->buildSpan('root-c')
                        ->start();

        $childSpan = $tracer->buildSpan(self::OPERATION_NAME)
                            ->addReference(Reference::FOLLOWS_FROM, $rootA)
                            ->addReference(Reference::CHILD_OF, $rootC)
                            ->addReference(Reference::CHILD_OF, $rootB)
                            ->start();

        $this->assertEquals($childSpan->getContext()->getTraceId(), $rootC->getContext()->getTraceId());
        $this->assertEquals($rootC->getContext()->getSpanId(), $childSpan->getContext()->getParentId());
    }

    public function test__ignoreActiveSpan__WithChildAlreadySet__ExpectException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
          'Usage of ignoreActiveSpan() after calling asChildOf() or addReference() is useless.'
        );

        $tracer = new MockTracer();
        $rootA  = $tracer->buildSpan('root-a')
                         ->start();

        $tracer->buildSpan(self::OPERATION_NAME)
               ->asChildOf($rootA)
               ->ignoreActiveSpan()
               ->start();
    }

    public function test__ignoreActiveSpan__WithReferenceAlreadySet__ExpectException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
          'Usage of ignoreActiveSpan() after calling asChildOf() or addReference() is useless.'
        );

        $tracer = new MockTracer();
        $rootA  = $tracer->buildSpan('root-a')
                         ->start();

        $tracer->buildSpan(self::OPERATION_NAME)
               ->addReference(Reference::CHILD_OF, $rootA)
               ->ignoreActiveSpan()
               ->start();
    }

    public function test__ignoreActiveSpan__WillReturnNullParent()
    {
        $tracer = new MockTracer();

        $span = $tracer->buildSpan(self::OPERATION_NAME)
                       ->ignoreActiveSpan()
                       ->start();

        $this->assertNull($span->getContext()->getParentId());
    }

    public function test__ignoreActiveSpan__NotBeCalled__WillReturnActiveSpanAsParent()
    {
        $tracer   = new MockTracer();
        $rootSpan = $tracer->buildSpan('root-a')
                           ->startActive();

        $childSpan = $tracer->buildSpan(self::OPERATION_NAME)
                            ->start();

        $this->assertEquals($childSpan->getContext()->getTraceId(), $rootSpan->getSpan()->getContext()->getSpanId());
        $this->assertEquals($rootSpan->getSpan()->getContext()->getSpanId(), $childSpan->getContext()->getParentId());
    }

    public function test__RUN_withTag__ReturnCorrectTags()
    {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->withTag('tag1', 'value1')
                         ->withTag('tag2', 'value2')
                         ->start();

        $this->assertEquals(['tag2' => 'value2', 'tag1' => 'value1'], $span->getTags());
    }

    public function test__NOTRUN_withTag__ReturnEmptyTags()
    {
        $tracer = new MockTracer();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)->start();

        $this->assertEquals([], $span->getTags());
    }

    public function test__RUN_withStartTimestamp__ReturnCorrectStartTime()
    {
        $tracer    = new MockTracer();
        $startTime = time();
        $span      = $tracer->buildSpan(self::OPERATION_NAME)
                            ->withStartTimestamp($startTime)
                            ->start();

        $this->assertEquals($startTime, $span->getStartTime());
    }

    public function test__NOTRUN_withStartTimestamp__StartTimeWillBeAutoSet()
    {
        $tracer = new MockTracer();
        $now    = time();
        $span   = $tracer->buildSpan(self::OPERATION_NAME)
                         ->start();

        $this->assertEquals($now, $span->getStartTime());
    }

    public function test__finishSpanOnClose__WillCallAllChildSpansFinish()
    {
        $tracer  = new MockTracer();
        $options = $tracer->buildSpan(self::OPERATION_NAME)
                          ->finishSpanOnClose(true)
                          ->getStartOptions();

        $this->assertTrue($options->shouldFinishSpanOnClose());
    }
}
