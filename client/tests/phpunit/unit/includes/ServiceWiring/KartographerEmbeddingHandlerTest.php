<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use ExtensionRegistry;
use Parser;
use ParserFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class KartographerEmbeddingHandlerTest extends ServiceWiringTestCase {

	public function testKartographerGlobeCoordinateFormatterDisabled(): void {
		$this->mockClientSettings( false );
		$this->mockParserFactory( false );

		$this->assertNull(
			$this->getService( 'WikibaseClient.KartographerEmbeddingHandler' )
		);
	}

	public function testConstruction(): void {
		$this->assumeKartographerIsLoaded();
		$this->mockClientSettings( true );
		$this->mockParserFactory( true );

		$this->assertInstanceOf(
			CachingKartographerEmbeddingHandler::class,
			$this->getService( 'WikibaseClient.KartographerEmbeddingHandler' )
		);
	}

	private function assumeKartographerIsLoaded() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' ) ) {
			$this->markTestSkipped(
				'ExtensionRegistry cannot be mocked (T257586) ' .
				'and Kartographer is not loaded'
			);
		}
	}

	private function mockClientSettings( bool $useKartographerGlobeCoordinateFormatter ): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'useKartographerGlobeCoordinateFormatter' => $useKartographerGlobeCoordinateFormatter,
			] ) );
	}

	private function mockParserFactory( bool $expectCall ): void {
		if ( $expectCall ) {
			$parser = $this->createMock( Parser::class );
			$parserFactory = $this->createMock( ParserFactory::class );
			$parserFactory->expects( $this->once() )
				->method( 'create' )
				->willReturn( $parser );
			$this->serviceContainer->expects( $this->once() )
				->method( 'getParserFactory' )
				->willReturn( $parserFactory );
		} else {
			$this->serviceContainer->expects( $this->never() )
				->method( 'getParserFactory' );
		}
	}

}
