<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class KartographerEmbeddingHandlerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockRepoSettings( true );
		$this->mockExtensionRegistry( true );
		$this->mockParserFactory( true );

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertInstanceOf( CachingKartographerEmbeddingHandler::class, $handler );
	}

	public function testWithoutSetting(): void {
		$this->mockRepoSettings( false );
		$this->serviceContainer->expects( $this->never() )->method( 'getExtensionRegistry' );
		$this->mockParserFactory( false );

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertNull( $handler );
	}

	public function testWithoutExtension(): void {
		$this->mockRepoSettings( true );
		$this->mockExtensionRegistry( false );
		$this->mockParserFactory( false );

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertNull( $handler );
	}

	private function mockRepoSettings( bool $useKartographerGlobeCoordinateFormatter ): void {
		$this->mockService( 'WikibaseRepo.Settings',
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
