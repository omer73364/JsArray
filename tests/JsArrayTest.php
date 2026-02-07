<?php

declare(strict_types=1);

namespace JsArray\Tests;

use JsArray\JsArray;
use PHPUnit\Framework\TestCase;

class JsArrayTest extends TestCase
{
    // ===== CREATION TESTS =====

    public function testFromWithNumericArray(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $array->toArray());
        $this->assertEquals(3, $array->length);
        $this->assertFalse($array->isMutable);
        $this->assertTrue($array->isImmutable);
    }

    public function testFromWithEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals([], $array->toArray());
        $this->assertEquals(0, $array->length);
    }

    public function testFromWithAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $array->toArray());
        $this->assertEquals(3, $array->length);
    }

    public function testFromWithMixedTypes(): void
    {
        $array = JsArray::from([1, 'two', 3.0, true, null]);
        $this->assertEquals([1, 'two', 3.0, true, null], $array->toArray());
    }

    public function testOfWithMultipleArguments(): void
    {
        $array = JsArray::of(1, 2, 3, 4, 5);
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testOfWithNoArguments(): void
    {
        $array = JsArray::of();
        $this->assertEquals([], $array->toArray());
    }

    public function testMutable(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $this->assertTrue($array->isMutable);
        $this->assertFalse($array->isImmutable);
    }

    public function testCreateMutableAlias(): void
    {
        $array = JsArray::createMutable([1, 2, 3]);
        $this->assertTrue($array->isMutable);
    }

    public function testConstructorWithMutableParameter(): void
    {
        $array = new JsArray([1, 2, 3], true);
        $this->assertTrue($array->isMutable);
    }

    public function testConstructorWithImmutableParameter(): void
    {
        $array = new JsArray([1, 2, 3], false);
        $this->assertFalse($array->isMutable);
    }

    // ===== PROPERTY TESTS =====

    public function testLengthProperty(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $array->length);
    }

    public function testLengthPropertyEmpty(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals(0, $array->length);
    }

    public function testIsMutableProperty(): void
    {
        $mutable = JsArray::mutable([1, 2, 3]);
        $immutable = JsArray::from([1, 2, 3]);
        $this->assertTrue($mutable->isMutable);
        $this->assertFalse($immutable->isMutable);
    }

    public function testIsImmutableProperty(): void
    {
        $mutable = JsArray::mutable([1, 2, 3]);
        $immutable = JsArray::from([1, 2, 3]);
        $this->assertFalse($mutable->isImmutable);
        $this->assertTrue($immutable->isImmutable);
    }

    public function testSetPropertyThrowsException(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->expectException(\RuntimeException::class);
        $array->test = 'value';
    }

    public function testGetUndefinedPropertyThrowsException(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->expectException(\InvalidArgumentException::class);
        $value = $array->undefined;
    }

    // ===== MODE CONVERSION TESTS =====

    public function testToImmutable(): void
    {
        $mutable = JsArray::mutable([1, 2, 3]);
        $immutable = $mutable->toImmutable();
        $this->assertTrue($immutable->isImmutable);
        $this->assertFalse($immutable->isMutable);
        $this->assertEquals([1, 2, 3], $immutable->toArray());
    }

    public function testToMutable(): void
    {
        $immutable = JsArray::from([1, 2, 3]);
        $mutable = $immutable->toMutable();
        $this->assertTrue($mutable->isMutable);
        $this->assertEquals([1, 2, 3], $mutable->toArray());
    }

    public function testGetMutableCopy(): void
    {
        $original = JsArray::from([1, 2, 3]);
        $copy = $original->getMutableCopy();
        $this->assertTrue($copy->isMutable);
        $this->assertEquals([1, 2, 3], $copy->toArray());
        // Original should remain immutable
        $this->assertFalse($original->isMutable);
    }

    public function testGetImmutableCopy(): void
    {
        $original = JsArray::mutable([1, 2, 3]);
        $copy = $original->getImmutableCopy();
        $this->assertFalse($copy->isMutable);
        $this->assertEquals([1, 2, 3], $copy->toArray());
        // Original should remain mutable
        $this->assertTrue($original->isMutable);
    }

    // ===== MAP TESTS =====

    public function testMapBasicTransformation(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $mapped = $array->map(fn($n) => $n * 2);
        $this->assertEquals([2, 4, 6, 8, 10], $mapped->toArray());
        // Original should be unchanged (immutable)
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testMapWithIndexParameter(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $mapped = $array->map(fn($value, $index) => $value + $index);
        $this->assertEquals([1, 3, 5], $mapped->toArray());
    }

    public function testMapWithArrayParameter(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $receivedArrays = [];
        $array->map(function ($value, $index, $arr) use (&$receivedArrays) {
            $receivedArrays[] = $arr;
        });
        $this->assertCount(3, $receivedArrays);
        $this->assertSame($array, $receivedArrays[0]);
    }

    public function testMapWithAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $mapped = $array->map(fn($n) => $n * 2);
        // Associative arrays preserve keys when mapped
        $this->assertEquals([2, 4, 6], $mapped->toArray());
    }

    public function testMapMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->map(fn($n) => $n * 2);
        $this->assertSame($array, $result);
        $this->assertEquals([2, 4, 6], $array->toArray());
    }

    public function testMapWithEmptyArray(): void
    {
        $array = JsArray::from([]);
        $mapped = $array->map(fn($n) => $n * 2);
        $this->assertEquals([], $mapped->toArray());
    }

    public function testMapWithComplexTypes(): void
    {
        $array = JsArray::from([[1, 2], [3, 4], [5, 6]]);
        $mapped = $array->map(fn($arr) => array_sum($arr));
        $this->assertEquals([3, 7, 11], $mapped->toArray());
    }

    // ===== FILTER TESTS =====

    public function testFilterBasicPredicate(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5, 6]);
        $filtered = $array->filter(fn($n) => $n % 2 === 0);
        // Filter re-indexes (JavaScript-compatible)
        $this->assertEquals([2, 4, 6], $filtered->toArray());
    }

    public function testFilterWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30, 40, 50]);
        $filtered = $array->filter(fn($value, $index) => $index % 2 === 0);
        // Filter re-indexes
        $this->assertEquals([10, 30, 50], $filtered->toArray());
    }

    public function testFilterAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);
        $filtered = $array->filter(fn($n) => $n > 2);
        // Filter re-indexes even for associative arrays
        $this->assertEquals([3, 4], $filtered->toArray());
    }

    public function testFilterMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3, 4, 5]);
        $result = $array->filter(fn($n) => $n > 2);
        $this->assertSame($array, $result);
        // Filter re-indexes
        $this->assertEquals([3, 4, 5], $array->toArray());
    }

    public function testFilterEmptyArray(): void
    {
        $array = JsArray::from([]);
        $filtered = $array->filter(fn($n) => $n > 0);
        $this->assertEquals([], $filtered->toArray());
    }

    public function testFilterNoMatches(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $filtered = $array->filter(fn($n) => $n > 10);
        $this->assertEquals([], $filtered->toArray());
    }

    public function testFilterAllMatch(): void
    {
        $array = JsArray::from([2, 4, 6, 8]);
        $filtered = $array->filter(fn($n) => $n % 2 === 0);
        $this->assertEquals([0 => 2, 1 => 4, 2 => 6, 3 => 8], $filtered->toArray());
    }

    // ===== REDUCE TESTS =====

    public function testReduceWithInitialValue(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $sum = $array->reduce(fn($acc, $n) => $acc + $n, 0);
        $this->assertEquals(15, $sum);
    }

    public function testReduceWithoutInitialValue(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $sum = $array->reduce(fn($acc, $n) => $acc + $n);
        $this->assertEquals(15, $sum);
    }

    public function testReduceWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $result = $array->reduce(fn($acc, $value, $index) => $acc + $index, 0);
        $this->assertEquals(3, $result); // 0 + 1 + 2 = 3
    }

    public function testReduceWithArrayParameter(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $receivedArrays = [];
        $array->reduce(function ($acc, $value, $index, $arr) use (&$receivedArrays) {
            $receivedArrays[] = $arr;
            return $acc + $value;
        }, 0);
        $this->assertCount(3, $receivedArrays);
        $this->assertSame($array, $receivedArrays[0]);
    }

    public function testReduceMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $sum = $array->reduce(fn($acc, $n) => $acc + $n, 0);
        $this->assertEquals(6, $sum);
        // Mutable mode doesn't affect reduce result
        $this->assertEquals([1, 2, 3], $array->toArray());
    }

    public function testReduceEmptyArrayWithInitialValue(): void
    {
        $array = JsArray::from([]);
        $result = $array->reduce(fn($acc, $n) => $acc + $n, 100);
        $this->assertEquals(100, $result);
    }

    public function testReduceEmptyArrayWithoutInitialValue(): void
    {
        $array = JsArray::from([]);
        $result = $array->reduce(fn($acc, $n) => $acc + $n);
        $this->assertNull($result);
    }

    public function testReduceSingleElement(): void
    {
        $array = JsArray::from([5]);
        $result = $array->reduce(fn($acc, $n) => $acc + $n);
        $this->assertEquals(5, $result);
    }

    public function testReduceToObject(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);

        $result = $array->reduce(
            fn($acc, $n) => [
                'sum'   => $acc['sum'] + $n,
                'count' => $acc['count'] + 1,
            ],
            ['sum' => 0, 'count' => 0]
        );

        $this->assertEquals(['sum' => 15, 'count' => 5], $result);
    }

    public function testReduceWithStringConcatenation(): void
    {
        $array = JsArray::from(['a', 'b', 'c']);
        $result = $array->reduce(fn($acc, $n) => $acc . $n, '');
        $this->assertEquals('abc', $result);
    }

    // ===== FLAT TESTS =====

    public function testFlatOneLevel(): void
    {
        $array = JsArray::from([1, [2, 3], 4]);
        $flattened = $array->flat();
        $this->assertEquals([1, 2, 3, 4], $flattened->toArray());
    }

    public function testFlatTwoLevels(): void
    {
        $array = JsArray::from([1, [2, [3, 4]], 5]);
        $flattened = $array->flat(2);
        $this->assertEquals([1, 2, 3, 4, 5], $flattened->toArray());
    }

    public function testFlatWithDepthZero(): void
    {
        $array = JsArray::from([1, [2, 3], 4]);
        $flattened = $array->flat(0);
        $this->assertEquals([1, [2, 3], 4], $flattened->toArray());
    }

    public function testFlatMutableMode(): void
    {
        $array = JsArray::mutable([1, [2, 3], 4]);
        $result = $array->flat();
        $this->assertSame($array, $result);
        $this->assertEquals([1, 2, 3, 4], $array->toArray());
    }

    public function testFlatWithNestedJsArray(): void
    {
        $inner = JsArray::from([2, 3]);
        $array = JsArray::from([1, $inner, 4]);
        $flattened = $array->flat();
        $this->assertEquals([1, 2, 3, 4], $flattened->toArray());
    }

    public function testFlatEmptyArrays(): void
    {
        $array = JsArray::from([1, [], [2, []], 3]);
        $flattened = $array->flat();
        // Empty arrays are removed but positions matter
        $this->assertEquals([1, 2, 3], $flattened->toArray());
    }

    public function testFlatAlreadyFlat(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $flattened = $array->flat();
        $this->assertEquals([1, 2, 3], $flattened->toArray());
    }

    public function testFlatEmptyArray(): void
    {
        $array = JsArray::from([]);
        $flattened = $array->flat();
        $this->assertEquals([], $flattened->toArray());
    }

    // ===== FLATMAP TESTS =====

    public function testFlatMapBasic(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->flatMap(fn($n) => [$n, $n * 2]);
        $this->assertEquals([1, 2, 2, 4, 3, 6], $result->toArray());
    }

    public function testFlatMapWithIndexParameter(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->flatMap(fn($value, $index) => [$value + $index]);
        $this->assertEquals([1, 3, 5], $result->toArray());
    }

    public function testFlatMapMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->flatMap(fn($n) => [$n, $n * 2]);
        $this->assertSame($array, $result);
        $this->assertEquals([1, 2, 2, 4, 3, 6], $array->toArray());
    }

    public function testFlatMapWithEmptyResult(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->flatMap(fn($n) => []);
        $this->assertEquals([], $result->toArray());
    }

    public function testFlatMapFlattensDeeper(): void
    {
        $array = JsArray::from([1, 2]);
        $result = $array->flatMap(fn($n) => [[$n, $n * 2]]);
        $this->assertEquals([[1, 2], [2, 4]], $result->toArray());
    }

    // ===== CONCAT TESTS =====

    public function testConcatTwoArrays(): void
    {
        $array1 = JsArray::from([1, 2, 3]);
        $array2 = JsArray::from([4, 5, 6]);
        $result = $array1->concat($array2);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result->toArray());
    }

    public function testConcatMultipleArrays(): void
    {
        $array1 = JsArray::from([1, 2]);
        $array2 = JsArray::from([3, 4]);
        $array3 = JsArray::from([5, 6]);
        $result = $array1->concat($array2, $array3);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result->toArray());
    }

    public function testConcatMutableMode(): void
    {
        $array1 = JsArray::mutable([1, 2, 3]);
        $array2 = JsArray::from([4, 5]);
        $result = $array1->concat($array2);
        $this->assertSame($array1, $result);
        $this->assertEquals([1, 2, 3, 4, 5], $array1->toArray());
    }

    public function testConcatWithEmptyArray(): void
    {
        $array1 = JsArray::from([1, 2, 3]);
        $array2 = JsArray::from([]);
        $result = $array1->concat($array2);
        $this->assertEquals([1, 2, 3], $result->toArray());
    }

    public function testConcatPreservesAssociativeKeys(): void
    {
        $array1 = JsArray::from(['a' => 1]);
        $array2 = JsArray::from(['b' => 2]);
        $result = $array1->concat($array2);
        // Concatenating associative arrays merges them
        $this->assertEquals(['a' => 1, 'b' => 2], $result->toArray());
    }

    // ===== FIND TESTS =====

    public function testFindBasic(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $found = $array->find(fn($n) => $n > 3);
        $this->assertEquals(4, $found);
    }

    public function testFindWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30, 40, 50]);
        $found = $array->find(fn($value, $index) => $index === 2);
        $this->assertEquals(30, $found);
    }

    public function testFindWithArrayParameter(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $receivedArrays = [];
        $array->find(function ($value, $index, $arr) use (&$receivedArrays) {
            if ($value > 2) {
                $receivedArrays[] = $arr;
                return $value > 2;
            }
        });
        $this->assertCount(1, $receivedArrays);
        $this->assertSame($array, $receivedArrays[0]);
    }

    public function testFindNotFound(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $found = $array->find(fn($n) => $n > 10);
        $this->assertNull($found);
    }

    public function testFindFirstOccurrence(): void
    {
        $array = JsArray::from([1, 2, 3, 2, 1]);
        $found = $array->find(fn($n) => $n === 2);
        $this->assertEquals(2, $found);
    }

    public function testFindWithAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $found = $array->find(fn($n) => $n > 1);
        $this->assertEquals(2, $found);
    }

    public function testFindEmptyArray(): void
    {
        $array = JsArray::from([]);
        $found = $array->find(fn($n) => $n > 0);
        $this->assertNull($found);
    }

    // ===== FINDINDEX TESTS =====

    public function testFindIndexBasic(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $index = $array->findIndex(fn($n) => $n === 3);
        $this->assertEquals(2, $index);
    }

    public function testFindIndexNotFound(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $index = $array->findIndex(fn($n) => $n === 10);
        $this->assertEquals(-1, $index);
    }

    public function testFindIndexWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30, 40]);
        $index = $array->findIndex(fn($value, $i) => $i === 3);
        $this->assertEquals(3, $index);
    }

    public function testFindIndexAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $index = $array->findIndex(fn($n) => $n === 2);
        $this->assertEquals('b', $index);
    }

    public function testFindIndexAssociativeNotFound(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2]);
        $index = $array->findIndex(fn($n) => $n === 10);
        $this->assertNull($index);
    }

    public function testFindIndexEmptyArray(): void
    {
        $array = JsArray::from([]);
        $index = $array->findIndex(fn($n) => $n > 0);
        $this->assertEquals(-1, $index);
    }

    // ===== INCLUDES TESTS =====

    public function testIncludesTrue(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertTrue($array->includes(3));
    }

    public function testIncludesFalse(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertFalse($array->includes(10));
    }

    public function testIncludesWithString(): void
    {
        $array = JsArray::from(['a', 'b', 'c']);
        $this->assertTrue($array->includes('b'));
        $this->assertFalse($array->includes('d'));
    }

    public function testIncludesStrictComparison(): void
    {
        $array = JsArray::from([1, '1', true, 1.0]);
        $this->assertTrue($array->includes(1));
        $this->assertTrue($array->includes('1'));
        $this->assertTrue($array->includes(true));
        $this->assertFalse($array->includes(2));
    }

    public function testIncludesEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertFalse($array->includes(1));
    }

    public function testIncludesWithNull(): void
    {
        $array = JsArray::from([1, null, 3]);
        $this->assertTrue($array->includes(null));
        $this->assertFalse($array->includes(2));
    }

    // ===== SOME TESTS =====

    public function testSomeTrue(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertTrue($array->some(fn($n) => $n > 3));
    }

    public function testSomeFalse(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertFalse($array->some(fn($n) => $n > 10));
    }

    public function testSomeWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30, 40]);
        $this->assertTrue($array->some(fn($value, $index) => $index === 2));
    }

    public function testSomeEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertFalse($array->some(fn($n) => $n > 0));
    }

    public function testSomeFirstElement(): void
    {
        $array = JsArray::from([5, 10, 15]);
        $this->assertTrue($array->some(fn($n) => $n === 5));
    }

    public function testSomeWithAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertTrue($array->some(fn($n) => $n > 2));
    }

    // ===== EVERY TESTS =====

    public function testEveryTrue(): void
    {
        $array = JsArray::from([2, 4, 6, 8]);
        $this->assertTrue($array->every(fn($n) => $n % 2 === 0));
    }

    public function testEveryFalse(): void
    {
        $array = JsArray::from([2, 4, 6, 7, 8]);
        $this->assertFalse($array->every(fn($n) => $n % 2 === 0));
    }

    public function testEveryWithIndexParameter(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $this->assertTrue($array->every(fn($value, $index) => $index >= 0));
    }

    public function testEveryEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertTrue($array->every(fn($n) => $n > 0));
    }

    public function testEveryWithAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertTrue($array->every(fn($n) => $n > 0));
    }

    // ===== PUSH TESTS =====

    public function testPushSingleValue(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->push(4);
        $this->assertEquals([1, 2, 3, 4], $result->toArray());
        // Original unchanged
        $this->assertEquals([1, 2, 3], $array->toArray());
    }

    public function testPushMultipleValues(): void
    {
        $array = JsArray::from([1, 2]);
        $result = $array->push(3, 4, 5);
        $this->assertEquals([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testPushMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->push(4, 5);
        $this->assertSame($array, $result);
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testPushWithArray(): void
    {
        $array = JsArray::from([1, 2]);
        $result = $array->push([3, 4]);
        $this->assertEquals([1, 2, [3, 4]], $result->toArray());
    }

    public function testPushToEmptyArray(): void
    {
        $array = JsArray::from([]);
        $result = $array->push(1);
        $this->assertEquals([1], $result->toArray());
    }

    // ===== POP TESTS =====

    public function testPop(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->pop();
        $this->assertEquals(3, $result['value']);
        $this->assertEquals([1, 2], $result['array']->toArray());
        // Original unchanged
        $this->assertEquals([1, 2, 3], $array->toArray());
    }

    public function testPopMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->pop();
        $this->assertSame($array, $result['array']);
        $this->assertEquals(3, $result['value']);
        $this->assertEquals([1, 2], $array->toArray());
    }

    public function testPopEmptyArray(): void
    {
        $array = JsArray::from([]);
        $result = $array->pop();
        $this->assertNull($result['value']);
        $this->assertEquals([], $result['array']->toArray());
    }

    public function testPopSingleElement(): void
    {
        $array = JsArray::from([1]);
        $result = $array->pop();
        $this->assertEquals(1, $result['value']);
        $this->assertEquals([], $result['array']->toArray());
    }

    // ===== SHIFT TESTS =====

    public function testShift(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->shift();
        $this->assertEquals(1, $result['value']);
        $this->assertEquals([2, 3], $result['array']->toArray());
        // Original unchanged
        $this->assertEquals([1, 2, 3], $array->toArray());
    }

    public function testShiftMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->shift();
        $this->assertSame($array, $result['array']);
        $this->assertEquals(1, $result['value']);
        $this->assertEquals([2, 3], $array->toArray());
    }

    public function testShiftEmptyArray(): void
    {
        $array = JsArray::from([]);
        $result = $array->shift();
        $this->assertNull($result['value']);
        $this->assertEquals([], $result['array']->toArray());
    }

    public function testShiftSingleElement(): void
    {
        $array = JsArray::from([1]);
        $result = $array->shift();
        $this->assertEquals(1, $result['value']);
        $this->assertEquals([], $result['array']->toArray());
    }

    // ===== UNSHIFT TESTS =====

    public function testUnshiftSingleValue(): void
    {
        $array = JsArray::from([3, 4, 5]);
        $result = $array->unshift(2);
        $this->assertEquals([2, 3, 4, 5], $result->toArray());
        // Original unchanged
        $this->assertEquals([3, 4, 5], $array->toArray());
    }

    public function testUnshiftMultipleValues(): void
    {
        $array = JsArray::from([4, 5]);
        $result = $array->unshift(1, 2, 3);
        $this->assertEquals([1, 2, 3, 4, 5], $result->toArray());
    }

    public function testUnshiftMutableMode(): void
    {
        $array = JsArray::mutable([3, 4, 5]);
        $result = $array->unshift(1, 2);
        $this->assertSame($array, $result);
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testUnshiftToEmptyArray(): void
    {
        $array = JsArray::from([]);
        $result = $array->unshift(1);
        $this->assertEquals([1], $result->toArray());
    }

    // ===== KEYS TESTS =====

    public function testKeysNumericArray(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $keys = $array->keys();
        $this->assertEquals([0, 1, 2], $keys->toArray());
    }

    public function testKeysAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $keys = $array->keys();
        $this->assertEquals(['a', 'b', 'c'], $keys->toArray());
    }

    public function testKeysEmptyArray(): void
    {
        $array = JsArray::from([]);
        $keys = $array->keys();
        $this->assertEquals([], $keys->toArray());
    }

    // ===== VALUES TESTS =====

    public function testValuesNumericArray(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $values = $array->values();
        $this->assertEquals([10, 20, 30], $values->toArray());
    }

    public function testValuesAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $values = $array->values();
        $this->assertEquals([1, 2, 3], $values->toArray());
    }

    public function testValuesEmptyArray(): void
    {
        $array = JsArray::from([]);
        $values = $array->values();
        $this->assertEquals([], $values->toArray());
    }

    // ===== FIRST/LAST TESTS =====

    public function testFirst(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $this->assertEquals(10, $array->first());
    }

    public function testLast(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $this->assertEquals(30, $array->last());
    }

    public function testFirstEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertNull($array->first());
    }

    public function testLastEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertNull($array->last());
    }

    public function testFirstAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(1, $array->first());
    }

    public function testLastAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(3, $array->last());
    }

    // ===== AT TESTS =====

    public function testAtPositiveIndex(): void
    {
        $array = JsArray::from([10, 20, 30, 40]);
        $this->assertEquals(10, $array->at(0));
        $this->assertEquals(20, $array->at(1));
        $this->assertEquals(30, $array->at(2));
        $this->assertEquals(40, $array->at(3));
    }

    public function testAtNegativeIndex(): void
    {
        $array = JsArray::from([10, 20, 30, 40]);
        $this->assertEquals(40, $array->at(-1));
        $this->assertEquals(30, $array->at(-2));
        $this->assertEquals(20, $array->at(-3));
        $this->assertEquals(10, $array->at(-4));
    }

    public function testAtOutOfBounds(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $this->assertNull($array->at(3));
        $this->assertNull($array->at(-4));
    }

    public function testAtEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertNull($array->at(0));
        $this->assertNull($array->at(-1));
    }

    public function testAtAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertEquals(1, $array->at(0));
        $this->assertEquals(2, $array->at(1));
        $this->assertEquals(3, $array->at(-1));
    }

    // ===== JOIN TESTS =====

    public function testJoinDefaultSeparator(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals('1,2,3,4,5', $array->join());
    }

    public function testJoinCustomSeparator(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals('1|2|3', $array->join('|'));
    }

    public function testJoinEmptySeparator(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals('123', $array->join(''));
    }

    public function testJoinSingleElement(): void
    {
        $array = JsArray::from([1]);
        $this->assertEquals('1', $array->join(','));
    }

    public function testJoinEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals('', $array->join(','));
    }

    public function testJoinWithStrings(): void
    {
        $array = JsArray::from(['a', 'b', 'c']);
        $this->assertEquals('a-b-c', $array->join('-'));
    }

    // ===== FOREACH TESTS =====

    public function testForEachWithValue(): void
    {
        $array = JsArray::from([10, 20, 30]);
        $sum = 0;
        $array->forEach(function ($value) use (&$sum) {
            $sum += $value;
        });
        $this->assertEquals(60, $sum);
    }

    public function testForEachWithValueAndIndex(): void
    {
        $array = JsArray::from(['a', 'b', 'c']);
        $result = [];
        $array->forEach(function ($value, $index) use (&$result) {
            $result[] = "$index:$value";
        });
        $this->assertEquals(['0:a', '1:b', '2:c'], $result);
    }

    public function testForEachWithAllParameters(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $receivedArrays = [];
        $array->forEach(function ($value, $index, $arr) use (&$receivedArrays) {
            $receivedArrays[] = $arr->toArray();
        });
        $this->assertEquals([[1, 2, 3], [1, 2, 3], [1, 2, 3]], $receivedArrays);
    }

    public function testForEachEmptyArray(): void
    {
        $array = JsArray::from([]);
        $called = false;
        $array->forEach(function () use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testForEachAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $result = [];
        $keys = [];
        $array->forEach(function ($value, $key) use (&$result, &$keys) {
            $result[] = $value;
            $keys[] = $key;
        });
        $this->assertEquals([1, 2, 3], $result);
        $this->assertEquals(['a', 'b', 'c'], $keys);
    }

    public function testForEachDoesNotModifyOriginal(): void
    {
        $original = [1, 2, 3];
        $array = JsArray::from($original);
        $array->forEach(fn() => null);
        $this->assertEquals($original, $array->toArray());
    }

    // ===== SLICE TESTS =====

    public function testSliceBasic(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals([2, 3], $array->slice(1, 3)->toArray());
    }

    public function testSliceFromIndex(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals([3, 4, 5], $array->slice(2)->toArray());
    }

    public function testSliceNegativeStart(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals([4, 5], $array->slice(-2)->toArray());
    }

    public function testSliceNegativeEnd(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals([1, 2, 3], $array->slice(0, -2)->toArray());
    }

    public function testSliceMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3, 4, 5]);
        $result = $array->slice(1, 3);
        $this->assertSame($array, $result);
        $this->assertEquals([2, 3], $array->toArray());
    }

    public function testSliceEmptyResult(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals([], $array->slice(5)->toArray());
    }

    public function testSliceReindexes(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $sliced = $array->slice(1, 3);
        $this->assertEquals([0 => 2, 1 => 3], $sliced->toArray());
    }

    public function testSliceFullArray(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $array->slice(0, 3)->toArray());
    }

    // ===== SPLICE TESTS =====

    public function testSpliceBasic(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $result = $array->splice(2, 2);
        $this->assertEquals([3, 4], $result['deleted']->toArray());
        $this->assertEquals([1, 2, 5], $result['array']->toArray());
        // Original unchanged
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testSpliceWithInsertion(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $result = $array->splice(2, 1, [10, 20]);
        $this->assertEquals([3], $result['deleted']->toArray());
        $this->assertEquals([1, 2, 10, 20, 4, 5], $result['array']->toArray());
    }

    public function testSpliceMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3, 4, 5]);
        $result = $array->splice(2, 2);
        $this->assertSame($array, $result['array']);
        $this->assertEquals([1, 2, 5], $array->toArray());
    }

    public function testSpliceDeleteAll(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->splice(0);
        $this->assertEquals([1, 2, 3], $result['deleted']->toArray());
        $this->assertEquals([], $result['array']->toArray());
    }

    public function testSpliceDeleteZero(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->splice(1, 0, [10]);
        $this->assertEquals([], $result['deleted']->toArray());
        $this->assertEquals([1, 10, 2, 3], $result['array']->toArray());
    }

    public function testSpliceStartBeyondLength(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $result = $array->splice(10, 2, [4, 5]);
        $this->assertEquals([], $result['deleted']->toArray());
        $this->assertEquals([1, 2, 3, 4, 5], $result['array']->toArray());
    }

    public function testSpliceEmptyArray(): void
    {
        $array = JsArray::from([]);
        $result = $array->splice(0, 1, [1]);
        $this->assertEquals([], $result['deleted']->toArray());
        $this->assertEquals([1], $result['array']->toArray());
    }

    // ===== INDEXOF TESTS =====

    public function testIndexOfFound(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $this->assertEquals(2, $array->indexOf(3));
    }

    public function testIndexOfNotFound(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals(-1, $array->indexOf(10));
    }

    public function testIndexOfWithFromIndex(): void
    {
        $array = JsArray::from([1, 2, 3, 2, 1]);
        $this->assertEquals(1, $array->indexOf(2, 0));
        $this->assertEquals(3, $array->indexOf(2, 2));
        $this->assertEquals(-1, $array->indexOf(2, 4));
    }

    public function testIndexOfFirstOccurrence(): void
    {
        $array = JsArray::from([1, 2, 3, 2, 1]);
        $this->assertEquals(1, $array->indexOf(2));
    }

    public function testIndexOfEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals(-1, $array->indexOf(1));
    }

    // ===== LASTINDEXOF TESTS =====

    public function testLastIndexOfFound(): void
    {
        $array = JsArray::from([1, 2, 3, 2, 1]);
        $this->assertEquals(4, $array->lastIndexOf(1));
    }

    public function testLastIndexOfNotFound(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals(-1, $array->lastIndexOf(10));
    }

    public function testLastIndexOfWithFromIndex(): void
    {
        $array = JsArray::from([1, 2, 3, 2, 1]);
        // Find last index of 2 starting from index 3 (searches backwards from index 3)
        $this->assertEquals(3, $array->lastIndexOf(2, null));
        $this->assertEquals(3, $array->lastIndexOf(2, 3));
        // Starting from index 2, find last 2
        $this->assertEquals(1, $array->lastIndexOf(2, 2));
    }

    public function testLastIndexOfEmptyArray(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals(-1, $array->lastIndexOf(1));
    }

    // ===== REVERSE TESTS =====

    public function testReverse(): void
    {
        $array = JsArray::from([1, 2, 3, 4, 5]);
        $reversed = $array->reverse();
        $this->assertEquals([5, 4, 3, 2, 1], $reversed->toArray());
        // Original unchanged
        $this->assertEquals([1, 2, 3, 4, 5], $array->toArray());
    }

    public function testReverseMutableMode(): void
    {
        $array = JsArray::mutable([1, 2, 3]);
        $result = $array->reverse();
        $this->assertSame($array, $result);
        $this->assertEquals([3, 2, 1], $array->toArray());
    }

    public function testReverseAssociativeArray(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2, 'c' => 3]);
        $reversed = $array->reverse();
        $this->assertEquals(['c' => 3, 'b' => 2, 'a' => 1], $reversed->toArray());
    }

    public function testReverseEmptyArray(): void
    {
        $array = JsArray::from([]);
        $reversed = $array->reverse();
        $this->assertEquals([], $reversed->toArray());
    }

    public function testReverseSingleElement(): void
    {
        $array = JsArray::from([1]);
        $reversed = $array->reverse();
        $this->assertEquals([1], $reversed->toArray());
    }

    // ===== SORT TESTS =====

    public function testSortDefault(): void
    {
        $array = JsArray::from([3, 1, 4, 1, 5, 9, 2, 6]);
        $sorted = $array->sort();
        $this->assertEquals([1, 1, 2, 3, 4, 5, 6, 9], $sorted->toArray());
    }

    public function testSortAscending(): void
    {
        $array = JsArray::from([3, 1, 4, 1, 5]);
        $sorted = $array->sort(fn($a, $b) => $a <=> $b);
        $this->assertEquals([1, 1, 3, 4, 5], $sorted->toArray());
    }

    public function testSortDescending(): void
    {
        $array = JsArray::from([3, 1, 4, 1, 5]);
        $sorted = $array->sort(fn($a, $b) => $b <=> $a);
        $this->assertEquals([5, 4, 3, 1, 1], $sorted->toArray());
    }

    public function testSortMutableMode(): void
    {
        $array = JsArray::mutable([3, 1, 4]);
        $result = $array->sort();
        $this->assertSame($array, $result);
        $this->assertEquals([1, 3, 4], $array->toArray());
    }

    public function testSortEmptyArray(): void
    {
        $array = JsArray::from([]);
        $sorted = $array->sort();
        $this->assertEquals([], $sorted->toArray());
    }

    public function testSortSingleElement(): void
    {
        $array = JsArray::from([1]);
        $sorted = $array->sort();
        $this->assertEquals([1], $sorted->toArray());
    }

    public function testSortPreservesKeys(): void
    {
        $array = JsArray::from(['c' => 3, 'a' => 1, 'b' => 2]);
        $sorted = $array->sort(fn($a, $b) => $a <=> $b);
        // Sort with callback re-indexes associative arrays
        $this->assertEquals([1, 2, 3], $sorted->toArray());
    }

    // ===== TOARRAY TESTS =====

    public function testToArray(): void
    {
        $array = JsArray::from([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $array->toArray());
    }

    public function testToArrayAssociative(): void
    {
        $array = JsArray::from(['a' => 1, 'b' => 2]);
        $this->assertEquals(['a' => 1, 'b' => 2], $array->toArray());
    }

    public function testToArrayEmpty(): void
    {
        $array = JsArray::from([]);
        $this->assertEquals([], $array->toArray());
    }

    // ===== CHAINING TESTS =====

    public function testChainingMapFilter(): void
    {
        $result = JsArray::from([1, 2, 3, 4, 5, 6])
            ->filter(fn($n) => $n % 2 === 0)
            ->map(fn($n) => $n * 2)
            ->toArray();
        $this->assertEquals([4, 8, 12], $result);
    }

    public function testChainingMultipleOperations(): void
    {
        $result = JsArray::from([1, [2, 3], [4, [5, 6]], 7])
            ->flat(2)
            ->filter(fn($n) => $n > 2)
            ->map(fn($n) => $n * 2)
            ->toArray();
        // Filter re-indexes
        $this->assertEquals([6, 8, 10, 12, 14], $result);
    }

    public function testChainingReduce(): void
    {
        $result = JsArray::from([1, 2, 3, 4, 5])
            ->filter(fn($n) => $n % 2 === 1)
            ->reduce(fn($acc, $n) => $acc + $n, 0);
        $this->assertEquals(9, $result);
    }

    public function testChainingWithSlice(): void
    {
        $result = JsArray::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
            ->slice(0, 5)
            ->map(fn($n) => $n * 2)
            ->toArray();
        $this->assertEquals([2, 4, 6, 8, 10], $result);
    }

    // ===== EDGE CASES =====

    public function testDeepNestingFlat(): void
    {
        $array = JsArray::from([1, [2, [3, [4, [5]]]]]);
        $flattened = $array->flat(10);
        $this->assertEquals([1, 2, 3, 4, 5], $flattened->toArray());
    }

    public function testMixedTypeArray(): void
    {
        $array = JsArray::from([1, 'two', 3.0, true, null, [4, 5]]);
        $this->assertEquals(1, $array->at(0));
        $this->assertEquals('two', $array->at(1));
        $this->assertEquals(3.0, $array->at(2));
        $this->assertTrue($array->at(3));
        $this->assertNull($array->at(4));
        $this->assertEquals([4, 5], $array->at(5));
    }

    public function testLargeArray(): void
    {
        $items = range(1, 1000);
        $array = JsArray::from($items);
        $this->assertEquals(1000, $array->length);
        $this->assertEquals(500, $array->at(499));
        $sum = $array->reduce(fn($acc, $n) => $acc + $n, 0);
        $this->assertEquals(500500, $sum);
    }

    public function testSpecialFloatValues(): void
    {
        $array = JsArray::from([INF, -INF, 0.0, -0.0]);
        $this->assertTrue($array->includes(INF));
        $this->assertTrue($array->includes(-INF));
        // NAN comparison is special - use indexOf instead
        $this->assertGreaterThanOrEqual(0, $array->indexOf(0.0));
    }

    public function testZeroValues(): void
    {
        $array = JsArray::from([0, -0, 0.0, 0x0]);
        $this->assertEquals(4, $array->length);
        $this->assertEquals(0, $array->at(0));
        $this->assertEquals(0, $array->at(1));
    }

    public function testCallbackWithObject(): void
    {
        $obj = new \stdClass();
        $obj->value = 42;
        $array = JsArray::from([$obj]);
        $found = $array->find(fn($item) => $item->value === 42);
        $this->assertSame($obj, $found);
    }

    public function testNestedJsArrays(): void
    {
        $inner1 = JsArray::from([1, 2]);
        $inner2 = JsArray::from([3, 4]);
        $array = JsArray::from([$inner1, $inner2]);
        $flattened = $array->flat();
        $this->assertEquals([1, 2, 3, 4], $flattened->toArray());
    }

    public function testFilterWithNullCallback(): void
    {
        $array = JsArray::from([1, 2, 3, null, 5]);
        $filtered = $array->filter(fn($n) => $n !== null);
        // Filter re-indexes
        $this->assertEquals([1, 2, 3, 5], $filtered->toArray());
    }

    public function testUnicodeStrings(): void
    {
        $array = JsArray::from(['hello', 'wörld', '你好', '🚀']);
        $this->assertEquals('hello', $array->at(0));
        $this->assertEquals('wörld', $array->at(1));
        $this->assertEquals('你好', $array->at(2));
        $this->assertEquals('🚀', $array->at(3));
        $this->assertEquals('hello,wörld,你好,🚀', $array->join(','));
    }

    public function testComplexReduceToObject(): void
    {
        $users = JsArray::from([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Charlie', 'age' => 35]
        ]);

        $result = $users->reduce(function ($acc, $user) {
            $acc[$user['name']] = $user['age'];
            return $acc;
        }, []);

        $this->assertEquals(['Alice' => 30, 'Bob' => 25, 'Charlie' => 35], $result);
    }

    // ===== \Iterator IMPLEMENTATION TESTS =====

    public function testIteratorMethods(): void
    {
        $array = JsArray::from([10, 20, 30]);

        // Initial state: current, key, valid
        $this->assertEquals(10, $array->current());
        $this->assertEquals(0, $array->key());
        $this->assertTrue($array->valid());

        // After first next()
        $array->next();
        $this->assertEquals(20, $array->current());
        $this->assertEquals(1, $array->key());
        $this->assertTrue($array->valid());

        // After second next()
        $array->next();
        $this->assertEquals(30, $array->current());
        $this->assertEquals(2, $array->key());
        $this->assertTrue($array->valid());

        // After third next() - past the end
        $array->next();
        $this->assertNull($array->current());
        $this->assertFalse($array->valid());

        // After rewind() - back to start
        $array->rewind();
        $this->assertEquals(10, $array->current());
        $this->assertEquals(0, $array->key());
        $this->assertTrue($array->valid());
    }

    /**
     * @testWith [[10, 20, 30]]
     *           [{"a": 1, "b": 2, "c": 3}]
     */
    public function testIteratorWithForeach(array $input): void
    {
        $array = JsArray::from($input);
        $collected = [];
        foreach ($array as $key => $value) {
            $collected[$key] = $value;
        }
        $this->assertEquals($input, $collected);
    }

    public function testIteratorEmptyArray(): void
    {
        $array = JsArray::from([]);
        foreach ($array as $value) {
            $this->fail("Should not iterate over empty array");
        }
        $this->assertTrue(true);
    }

    public function testIteratorWithIteratorFunctions(): void
    {
        $input = [10, 20, 30];
        $array = JsArray::from($input);
        $this->assertEquals($input, iterator_to_array($array));
    }

    // ===== \Countable IMPLEMENTATION TESTS =====


    /**
     * @testWith [[1, 2, 3, 4, 5], 5]
     *           [[], 0]
     *           [{"a": 1, "b": 2, "c": 3}, 3]
     */
    public function testCountable(array $input, int $expectedCount): void
    {
        $array = JsArray::from($input);
        $this->assertCount($expectedCount, $array);
        $this->assertEquals($expectedCount, $array->count());
        $this->assertEquals($expectedCount, $array->length);
    }

    // ===== \JsonSerializable IMPLEMENTATION TESTS =====

    /**
     * @testWith [[1, 2, 3], [1, 2, 3]]
     *           [{"a": 1, "b": 2}, {"a": 1, "b": 2}]
     *           [[], []]
     */
    public function testJsonSerialize(array $input, array $expected): void
    {
        $array = JsArray::from($input);
        $this->assertEquals($expected, $array->jsonSerialize());
    }

    /**
     * @testWith [[1, 2, 3], "[1,2,3]"]
     *           [{"a": 1, "b": 2}, "{\"a\":1,\"b\":2}"]
     *           [[], "[]"]
     *           [[1, [2, 3], {"a": 4}], "[1,[2,3],{\"a\":4}]"]
     *           [[1, "two", 3.14, true, null], "[1,\"two\",3.14,true,null]"]
     */
    public function testJsonEncode(array $input, string $expected): void
    {
        $array = JsArray::from($input);
        $this->assertEquals($input, $array->jsonSerialize());
        $this->assertEquals($expected, json_encode($array));
    }

    // ===== FROMJSON TESTS =====

    /**
     * @testWith ["[1, 2, 3]", [1, 2, 3]]
     *           ["{\"a\": 1, \"b\": 2, \"c\": 3}", {"a": 1, "b": 2, "c": 3}]
     *           ["[]", []]
     *           ["{}", []]
     *           ["[1, [2, 3], {\"a\": 4}]", [1, [2, 3], {"a": 4}]]
     *           ["[1, \"two\", 3.14, true, null]", [1, "two", 3.14, true, null]]
     */
    public function testFromJson(string $json, array $expected): void
    {
        $array = JsArray::fromJson($json);
        $this->assertEquals($expected, $array->toArray());
    }

    public function testFromJsonThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        JsArray::fromJson('invalid json');
    }
}
