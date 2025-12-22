<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Registration\ExtensionRegistry;
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
		$this->serviceContainer->expects( $this->never() )->method( 'getExtensionRegistry' );
		$this->mockParserFactory( false );

		$this->assertNull(
			$this->getService( 'WikibaseClient.KartographerEmbeddingHandler' )
		);
	}

	public function testKartographerNotLoaded(): void {
		$this->mockClientSettings( true );
		$this->mockExtensionRegistry( false );
		$this->mockParserFactory( false );

		$this->assertNull(
			$this->getService( 'WikibaseClient.KartographerEmbeddingHandler' )
		);
	}

	public function testConstruction(): void {
		$this->mockClientSettings( true );
		$this->mockExtensionRegistry( true );
		$this->mockParserFactory( true );

		$this->assertInstanceOf(
			CachingKartographerEmbeddingHandler::class,
			$this->getService( 'WikibaseClient.KartographerEmbeddingHandler' )
		);
	}

	private function mockClientSettings( bool $useKartographerGlobeCoordinateFormatter ): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'useKartographerGlobeCoordinateFormatter' => $useKartographerGlobeCoordinateFormatter,
			] ) );
	}

	private function mockExtensionRegistry( bool $isKartographerLoaded ): void {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'Kartographer' )
			->willReturn( $isKartographerLoaded );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getExtensionRegistry' )
			->willReturn( $extensionRegistry );
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
