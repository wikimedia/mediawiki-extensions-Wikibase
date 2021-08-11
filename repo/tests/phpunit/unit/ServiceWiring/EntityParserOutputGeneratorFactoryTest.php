<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityParserOutputGeneratorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'preferredGeoDataProperties' => [],
				'preferredPageImagesProperties' => [],
				'globeUris' => [],
			] ) );
		$this->mockService( 'WikibaseRepo.EntityViewFactory',
			new DispatchingEntityViewFactory( [] ) );
		$this->mockService( 'WikibaseRepo.EntityMetaTagsCreatorFactory',
			new DispatchingEntityMetaTagsCreatorFactory( [] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityDataFormatProvider',
			new EntityDataFormatProvider() );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseRepo.ItemUrlParser',
			$this->createMock( SuffixEntityIdParser::class ) );
		$this->mockService( 'WikibaseRepo.KartographerEmbeddingHandler',
			$this->createMock( CachingKartographerEmbeddingHandler::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getStatsdDataFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getRepoGroup' );

		$this->assertInstanceOf(
			EntityParserOutputGeneratorFactory::class,
			$this->getService( 'WikibaseRepo.EntityParserOutputGeneratorFactory' )
		);
	}

}
