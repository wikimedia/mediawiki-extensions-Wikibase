<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use ExtensionRegistry;
use Parser;
use ParserFactory;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class KartographerEmbeddingHandlerTest extends ServiceWiringTestCase {

	/** @var ExtensionRegistry */
	private $extensionRegistry;

	/** @var array */
	private $originalLoaded;

	protected function setUp(): void {
		parent::setUp();

		// TODO overriding ExtensionRegistry’s loaded list is ugly (T257586)

		$this->extensionRegistry = TestingAccessWrapper::newFromObject(
			ExtensionRegistry::getInstance()
		);

		$this->originalLoaded = $this->extensionRegistry->loaded;

		// pretend Kartographer is loaded by default
		// (tests may change this by overriding the array again)
		$this->extensionRegistry->loaded = [ 'Kartographer' => [] ];
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->extensionRegistry->loaded = $this->originalLoaded;
	}

	public function testConstruction(): void {
		$this->mockRepoSettings( true );
		$this->mockParserFactory( true );

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertInstanceOf( CachingKartographerEmbeddingHandler::class, $handler );
	}

	public function testWithoutSetting(): void {
		$this->mockRepoSettings( false );
		$this->mockParserFactory( false );

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertNull( $handler );
	}

	public function testWithoutExtension(): void {
		$this->mockRepoSettings( true );
		$this->mockParserFactory( false );
		$this->extensionRegistry->loaded = [];

		$handler = $this->getService( 'WikibaseRepo.KartographerEmbeddingHandler' );

		$this->assertNull( $handler );
	}

	private function mockRepoSettings( bool $useKartographerGlobeCoordinateFormatter ): void {
		$this->mockService( 'WikibaseRepo.Settings',
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
