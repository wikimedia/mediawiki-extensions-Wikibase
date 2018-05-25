<?php

namespace Wikibase\Repo\Tests\Hooks\Formatters;

use Language;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikimedia\Assert\ParameterElementTypeException;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLinkFormatterFactoryTest extends \MediaWikiTestCase {

	public function testGivenEntityTypeWithRegisteredCallback_returnsCallbackResult() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language );
			},
		] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'item' )
		);
	}

	public function testGivenUnknownEntityType_returnsDefaultFormatter() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), [] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'unknown-type' )
		);
	}

	/**
	 * @dataProvider notACallbackProvider
	 */
	public function testGivenNotArrayOfCallbacks_throwsException( $notCallbacks ) {
		$this->setExpectedException( ParameterElementTypeException::class );
		new EntityLinkFormatterFactory( Language::factory( 'en' ), $notCallbacks );
	}

	/**
	 * @dataProvider notAStringProvider
	 */
	public function testGivenEntityTypeNotAString_getLinkFormatterThrowsException( $notAString ) {
		$this->setExpectedException( ParameterTypeException::class );
		( new EntityLinkFormatterFactory( Language::factory( 'en' ), [] ) )
			->getLinkFormatter( $notAString );
	}

	public function testGivenSameTypeAndLanguage_getLinkFormatterCachesResult() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language );
			},
		] );

		$this->assertSame(
			$factory->getLinkFormatter( 'item' ),
			$factory->getLinkFormatter( 'item' )
		);
	}

	public function notACallbackProvider() {
		return [
			[ [ null ] ],
			[ [ 'asdf' ] ],
			[ [ 1, 2, 3 ] ],
			[ [
				'foo' => function () {
				},
				'bar' => null,
			] ],
		];
	}

	public function notAStringProvider() {
		return [
			[ null ],
			[ false ],
			[ 1 ],
		];
	}

}
