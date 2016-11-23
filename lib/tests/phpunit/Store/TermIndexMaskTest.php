<?php

namespace Wikibase\Lib\Tests;

use MWException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\TermIndexMask;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Lib\Store\TermIndexMask
 *
 * @group Wikibase
 * @group WikibaseTerm
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 */
class TermIndexMaskTest extends PHPUnit_Framework_TestCase {

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
		$mask = new TermIndexMask( $fields );

		$this->assertEquals( isset( $fields['termType'] ) ? $fields['termType'] : null, $mask->getTermType() );
		$this->assertEquals( isset( $fields['termLanguage'] ) ? $fields['termLanguage'] : null, $mask->getLanguage() );
		$this->assertEquals( isset( $fields['termText'] ) ? $fields['termText'] : null, $mask->getText() );
	}

	public function testGivenInvalidField_constructorThrowsException() {
		$this->setExpectedException( MWException::class );
		new TermIndexMask( [
			'entityType' => Item::ENTITY_TYPE,
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'foo',
		] );
	}

	public function testClone() {
		$mask = new TermIndexMask( [ 'termText' => 'Foo' ] );

		$clone = clone $mask;
		$this->assertEquals( $mask, $clone, 'clone must be equal to original' );
	}

}
