<?php

namespace Wikibase\Repo\Tests\Hooks\Formatters;

use Language;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * @covers \Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLinkFormatterFactoryTest extends MediaWikiIntegrationTestCase {

	public function testGivenEntityTypeWithRegisteredCallback_returnsCallbackResult() {
		$factory = new EntityLinkFormatterFactory( $this->getEntityTitleTextLookup(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language, $this->getEntityTitleTextLookup() );
			},
		] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'item', Language::factory( 'en' ) )
		);
	}

	private function getEntityTitleTextLookup() {
		return $this->createMock( EntityTitleTextLookup::class );
	}

	public function testGivenUnknownEntityType_returnsDefaultFormatter() {
		$factory = new EntityLinkFormatterFactory( $this->getEntityTitleTextLookup(),  [] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'unknown-type', Language::factory( 'en' ) )
		);
	}

	/**
	 * @dataProvider notACallbackProvider
	 */
	public function testGivenNotArrayOfCallbacks_throwsException( $notCallbacks ) {
		$this->expectException( ParameterElementTypeException::class );
		new EntityLinkFormatterFactory( $this->getEntityTitleTextLookup(), $notCallbacks );
	}

	public function testGivenSameTypeAndLanguage_getLinkFormatterCachesResult() {
		$factory = new EntityLinkFormatterFactory( $this->getEntityTitleTextLookup(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter( $language, $this->getEntityTitleTextLookup() );
			},
		] );

		$this->assertSame(
			$factory->getLinkFormatter( 'item', Language::factory( 'en' ) ),
			$factory->getLinkFormatter( 'item', Language::factory( 'en' ) )
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

}
