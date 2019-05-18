<?php

namespace Wikibase\Repo\Tests;

use Language;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;

/**
 * @covers \Wikibase\Repo\EntityIdHtmlLinkFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactoryTest extends TestCase {
	use PHPUnit4And6Compat;

	private function getFormatterFactory() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		return new EntityIdHtmlLinkFormatterFactory(
			$titleLookup,
			$languageNameLookup
		);
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_HTML, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormatter( Language::factory( 'en' ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
		$this->assertInstanceOf( DispatchingEntityIdHtmlLinkFormatter::class, $formatter );
	}

	public function testPassesLanguageToFormatterCallbacks() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$language = Language::factory( 'en' );

		$callbackMock = $this->getMock( \stdClass::class, [ '__invoke' ] );
		$callbackMock->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback(
				function ( $passedLanguage ) use ( $language ) {
					$this->assertSame( $language, $passedLanguage );
					return $this->createMock( EntityIdFormatter::class );
				}
			);

		$factory = new EntityIdHtmlLinkFormatterFactory(
			$titleLookup,
			$languageNameLookup,
			[ 'foo' => $callbackMock ]
		);

		$factory->getEntityIdFormatter( $language );
	}

}
