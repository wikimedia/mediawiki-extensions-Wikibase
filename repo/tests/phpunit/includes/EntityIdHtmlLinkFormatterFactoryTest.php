<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;

/**
 * @covers \Wikibase\Repo\EntityIdHtmlLinkFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactoryTest extends TestCase {

	private function getFormatterFactory() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		return new EntityIdHtmlLinkFormatterFactory(
			$titleLookup
		);
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_HTML, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormatter( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
		$this->assertInstanceOf( DispatchingEntityIdHtmlLinkFormatter::class, $formatter );
	}

	public function testPassesLanguageToFormatterCallbacks() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );

		$callbackMock = $this->getMockBuilder( \stdClass::class )
			->addMethods( [ '__invoke' ] )
			->getMock();
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
			[ 'foo' => $callbackMock ]
		);

		$factory->getEntityIdFormatter( $language );
	}

}
