<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Store\TermIndexSearchCriteria
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexSearchCriteriaTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideFieldsForConstructor() {
		return [
			[
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'foo',
				]
			],
			[
				[
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
				]
			],
			[
				[
					'termText' => 'foo',
				]
			],
			[
				[]
			]
		];
	}

	/**
	 * @dataProvider provideFieldsForConstructor
	 */
	public function testConstructor( array $fields ) {
		$mask = new TermIndexSearchCriteria( $fields );

		$this->assertEquals( isset( $fields['termType'] ) ? $fields['termType'] : null, $mask->getTermType() );
		$this->assertEquals( isset( $fields['termLanguage'] ) ? $fields['termLanguage'] : null, $mask->getLanguage() );
		$this->assertEquals( isset( $fields['termText'] ) ? $fields['termText'] : null, $mask->getText() );
	}

	public function testGivenInvalidField_constructorThrowsException() {
		$this->setExpectedException( ParameterAssertionException::class );
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
		$this->setExpectedException( ParameterAssertionException::class );
		new TermIndexSearchCriteria( $fields );
	}

	public function testClone() {
		$mask = new TermIndexSearchCriteria( [ 'termText' => 'Foo' ] );

		$clone = clone $mask;
		$this->assertEquals( $mask, $clone, 'clone must be equal to original' );
	}

}
