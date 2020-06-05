<?php

namespace Wikibase\Repo\Tests\Hooks\Formatters;

use Language;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikimedia\Assert\ParameterElementTypeException;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLinkFormatterFactoryTest extends \MediaWikiTestCase {

	public function testGivenEntityTypeWithRegisteredCallback_returnsCallbackResult() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), $this->getEntityTitleTextLookup(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language, $this->getEntityTitleTextLookup() );
			},
		] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'item' )
		);
	}

	private function getEntityTitleTextLookup() {
		return $this->createMock( EntityTitleTextLookup::class );
	}

	public function testGivenUnknownEntityType_returnsDefaultFormatter() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), $this->getEntityTitleTextLookup(),  [] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'unknown-type' )
		);
	}

	/**
	 * @dataProvider notACallbackProvider
	 */
	public function testGivenNotArrayOfCallbacks_throwsException( $notCallbacks ) {
		$this->expectException( ParameterElementTypeException::class );
		new EntityLinkFormatterFactory( Language::factory( 'en' ), $this->getEntityTitleTextLookup(), $notCallbacks );
	}

	/**
	 * @dataProvider notAStringProvider
	 */
	public function testGivenEntityTypeNotAString_getLinkFormatterThrowsException( $notAString ) {
		$this->expectException( ParameterTypeException::class );
		( new EntityLinkFormatterFactory( Language::factory( 'en' ), $this->getEntityTitleTextLookup(), [] ) )
			->getLinkFormatter( $notAString );
	}

	public function testGivenSameTypeAndLanguage_getLinkFormatterCachesResult() {
		$factory = new EntityLinkFormatterFactory( Language::factory( 'en' ), $this->getEntityTitleTextLookup(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language, $this->getEntityTitleTextLookup() );
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
