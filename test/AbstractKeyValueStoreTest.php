<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Upward\Test;

use Magento\Upward\AbstractKeyValueStore;
use PHPUnit\Framework\TestCase;
use function BeBat\Verify\verify;

class AbstractKeyValueStoreTest extends TestCase
{
    public function emptyKeyDataProvider(): array
    {
        return [
            [''],
            ['  '],
        ];
    }

    public function testCount(): void
    {
        $subject = $this->getSubject(['a', 'b', 'c']);

        verify($subject->count())->is()->sameAs(3);
    }

    public function testGet(): void
    {
        $subject = $this->getSubject(['key' => 'some value']);

        verify($subject->get('key'))->is()->sameAs('some value');
        verify($subject->get(''))->is()->null();
        verify($subject->get('missing'))->is()->null();

        $data = [
            'key0' => 'value 0',
            'key1' => [
                'key10' => 'value 10',
                'key11' => [
                    'key110' => 'value 110',
                ],
            ],
        ];

        $subject = $this->getSubject($data);

        verify($subject->get('key0'))->is()->sameAs('value 0');
        verify($subject->get('key1.key10'))->is()->sameAs('value 10');
        verify($subject->get('key1.key11.key110'))->is()->sameAs('value 110');

        $value1 = $subject->get('key1');

        verify($value1)->is()->instanceOf(AbstractKeyValueStore::class);
        verify($value1->get('key10'))->is()->sameAs('value 10');

        $value11 = $subject->get('key1.key11');

        verify($value11)->is()->instanceOf(AbstractKeyValueStore::class);
        verify($value11->get('key110'))->is()->sameAs('value 110');

        $subject = $this->getSubject(['a', 'b', 'c']);

        verify($subject->get(0))->is()->sameAs('a');
        verify($subject->get('0'))->is()->sameAs('a');
        verify($subject->get(''))->is()->null();
    }

    public function testGetExistingParentLookup(): void
    {
        $subject = $this->getSubject([
            'key0' => [
                'key00' => 'value',
            ],
        ]);

        verify($subject->getExistingParentLookup('key0.key00'))->is()->sameAs('key0.key00');
        verify($subject->getExistingParentLookup('key0.key00.key000'))->is()->sameAs('key0.key00');
        verify($subject->getExistingParentLookup('key0.key01'))->is()->sameAs('key0');
        verify($subject->getExistingParentLookup('key1'))->is()->sameAs('');
    }

    public function testGetKeys(): void
    {
        $subject = $this->getSubject();

        verify($subject->getKeys())->is()->sameAs([]);

        $subject = $this->getSubject(['key1' => 'value 1', 'key2' => 'value 2']);

        verify($subject->getKeys())->withoutOrder()->is()->equalTo(['key1', 'key2']);
    }

    public function testHas(): void
    {
        $subject = $this->getSubject();

        verify($subject->has('anything'))->is()->false();

        $data = [
            'key0' => 'value 0',
            'key1' => [
                'key10' => 'value 10',
                'key11' => [
                    'key110' => 'value 110',
                    'key111' => [],
                ],
            ],
        ];

        $subject = $this->getSubject($data);

        verify($subject->has('key0'))->is()->true();
        verify($subject->has('key0.anything'))->is()->false();
        verify($subject->has('key1'))->is()->true();
        verify($subject->has('key2'))->is()->false();
        verify($subject->has('key2.anything'))->is()->false();

        verify($subject->has('key1.key10'))->is()->true();
        verify($subject->has('key1.key11'))->is()->true();
        verify($subject->has('key1.key12'))->is()->false();

        verify($subject->has('key1.key11.key110'))->is()->true();
        verify($subject->has('key1.key11.key111'))->is()->true();
        verify($subject->has('key1.key11.key112'))->is()->false();
    }

    public function testIteratorFunction(): void
    {
        $subject = $this->getSubject(['a', 'b', 'c']);

        $subject->rewind();

        verify($subject->valid())->is()->true();
        verify($subject->current())->is()->sameAs('a');

        $subject->next();
        verify($subject->valid())->is()->true();
        verify($subject->current())->is()->sameAs('b');

        $subject->next();
        verify($subject->valid())->is()->true();
        verify($subject->current())->is()->sameAs('c');

        $subject->next();
        verify($subject->valid())->is()->false();

        $subject->rewind();
        verify($subject->valid())->is()->true();
        verify($subject->current())->is()->sameAs('a');
    }

    /**
     * @depends testGet
     */
    public function testSet(): void
    {
        $subject = $this->getSubject();

        $subject->set('key0', 'value 0');
        verify($subject->get('key0'))->is()->sameAs('value 0');

        $subject->set('key1.key10', 'value 10');
        verify($subject->get('key1.key10'))->is()->sameAs('value 10');

        $subject->set('key1.key11.key110', 'value 110');
        verify($subject->get('key1.key11.key110'))->is()->sameAs('value 110');
    }

    /**
     * @depends testGet
     */
    public function testSetConvertStore(): void
    {
        $subject = $this->getSubject();
        $child   = $this->getSubject(['key' => 'value']);

        $subject->set('child', $child);

        // $child should be converted to an array so traversal works as expected
        verify($subject->get('child.key'))->is()->sameAs('value');
    }

    /**
     * @dataProvider emptyKeyDataProvider
     */
    public function testSetEmptyKey(string $key): void
    {
        $subject = $this->getSubject();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot set a value for an empty lookup.');

        $subject->set($key, 'some value');
    }

    public function testSetExistingKey(): void
    {
        $subject = $this->getSubject(['key' => 'value']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lookup already exists in store.');

        $subject->set('key', 'another value');
    }

    public function testSetExistingKeyToArray(): void
    {
        $subject = $this->getSubject(['key' => 'value']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lookup would overwrite existing scalar value with an array.');

        $subject->set('key.child', 'some value');
    }

    public function testSetMergeValues(): void
    {
        $subject = $this->getSubject(['key' => ['a']]);

        $subject->set('key', ['a', 'b', 'c']);

        verify($subject->get('key')->toArray())->is()->sameAs(['a', 'b', 'c']);
    }

    public function testToArray(): void
    {
        $subject = $this->getSubject();

        verify($subject->toArray())->is()->sameAs([]);

        $data = ['key1' => 'value 1', 'key2' => 'value 2'];

        $subject = $this->getSubject($data);

        verify($subject->toArray())->is()->sameAs($data);
    }

    private function getSubject(array $data = []): AbstractKeyValueStore
    {
        return new class($data) extends AbstractKeyValueStore {
        };
    }
}
