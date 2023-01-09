<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks\Formatters;

use Language;
use MediaWiki\Languages\LanguageFactory;
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
		$factory = new EntityLinkFormatterFactory(
			$this->createMock( EntityTitleTextLookup::class ),
			$this->getServiceContainer()->getLanguageFactory(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter(
					$language,
					$this->createMock( EntityTitleTextLookup::class ),
					$this->getServiceContainer()->getLanguageFactory()
				);
			},
		] );

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'item', $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) )
		);
	}

	public function testGivenUnknownEntityType_returnsDefaultFormatter() {
		$factory = new EntityLinkFormatterFactory(
			$this->createMock( EntityTitleTextLookup::class ),
			$this->createMock( LanguageFactory::class ),
			[]
		);

		$this->assertInstanceOf(
			DefaultEntityLinkFormatter::class,
			$factory->getLinkFormatter( 'unknown-type', $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) )
		);
	}

	/**
	 * @dataProvider notACallbackProvider
	 */
	public function testGivenNotArrayOfCallbacks_throwsException( $notCallbacks ) {
		$this->expectException( ParameterElementTypeException::class );
		new EntityLinkFormatterFactory(
			$this->createMock( EntityTitleTextLookup::class ),
			$this->createMock( LanguageFactory::class ),
			$notCallbacks
		);
	}

	public function testGivenSameTypeAndLanguage_getLinkFormatterCachesResult() {
		$factory = new EntityLinkFormatterFactory(
			$this->createMock( EntityTitleTextLookup::class ),
			$this->getServiceContainer()->getLanguageFactory(), [
			'item' => function ( $language ) {
				return new DefaultEntityLinkFormatter(
					$language,
					$this->createMock( EntityTitleTextLookup::class ),
					$this->getServiceContainer()->getLanguageFactory()
				);
			},
		] );

		$languageMock = $this->createMock( Language::class );
		$languageMock->method( 'getCode' )->willReturn( 'en' );
		$this->assertSame(
			$factory->getLinkFormatter( 'item', $languageMock ),
			$factory->getLinkFormatter( 'item', $languageMock )
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
