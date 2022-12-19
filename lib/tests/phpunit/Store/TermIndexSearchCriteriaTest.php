<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\Lib\Store\TermIndexSearchCriteria
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexSearchCriteriaTest extends \PHPUnit\Framework\TestCase {

	public function provideFieldsForConstructor() {
		return [
			[
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				],
			],
			[
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
				],
			],
			[
				[
					'termText' => 'foo',
				],
			],
			[
				[],
			],
		];
	}

	/**
	 * @dataProvider provideFieldsForConstructor
	 */
	public function testConstructor( array $fields ) {
		$mask = new TermIndexSearchCriteria( $fields );

		$this->assertSame( $fields['termType'] ?? null, $mask->getTermType() );
		$this->assertSame( $fields['termLanguage'] ?? null, $mask->getLanguage() );
		$this->assertSame( $fields['termText'] ?? null, $mask->getText() );
	}

	public function testGivenInvalidField_constructorThrowsException() {
		$this->expectException( ParameterAssertionException::class );
		new TermIndexSearchCriteria( [
			'entityType' => Item::ENTITY_TYPE,
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
	}

	public function provideInvalidValues() {
		$goodFields = [
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		];

		return [
			'non-string term type' => [ array_merge( $goodFields, [ 'termType' => 100 ] ) ],
			'empty term type' => [ array_merge( $goodFields, [ 'termType' => '' ] ) ],
			'invalid term type' => [ array_merge( $goodFields, [ 'termType' => 'foo' ] ) ],
			'non-string term language' => [ array_merge( $goodFields, [ 'termLanguage' => 100 ] ) ],
			'empty term language' => [ array_merge( $goodFields, [ 'termLanguage' => '' ] ) ],
			'non-string term text' => [ array_merge( $goodFields, [ 'termText' => 100 ] ) ],
			'empty term text' => [ array_merge( $goodFields, [ 'termText' => '' ] ) ],
		];
	}

	/**
	 * @dataProvider provideInvalidValues
	 */
	public function testGivenInvalidValues_constructorThrowsException( $fields ) {
		$this->expectException( ParameterAssertionException::class );
		new TermIndexSearchCriteria( $fields );
	}

	public function testClone() {
		$mask = new TermIndexSearchCriteria( [ 'termText' => 'Foo' ] );

		$clone = clone $mask;
		$this->assertEquals( $mask, $clone, 'clone must be equal to original' );
	}

}
