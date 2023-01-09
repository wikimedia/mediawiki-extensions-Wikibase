<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use HashConfig;
use Language;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultValueFormatterBuildersTest extends ServiceWiringTestCase {

	public function testWithoutKartographer(): void {
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
				'geoShapeStorageBaseUrl' => '',
				'tabularDataStorageBaseUrl' => '',
				'sharedCacheDuration' => 0,
				'entitySchemaNamespace' => 0,
				'useKartographerMaplinkInWikitext' => false,
			] ) );
		$this->serviceContainer->expects( $this->never() )
			->method( 'getParserFactory' );
		$this->mockService( 'WikibaseClient.TermLookup',
			new NullPrefetchingTermLookup() );
		$this->mockService( 'WikibaseClient.RedirectResolvingLatestRevisionLookup',
			$this->createMock( RedirectResolvingLatestRevisionLookup::class ) );
		$userLanguage = $this->createMock( Language::class );
		$userLanguage->expects( $this->once() )
			->method( 'getCode' )
			->willReturn( 'en' );
		$this->mockService( 'WikibaseClient.UserLanguage',
			$userLanguage );
		$this->mockService( 'WikibaseClient.RepoItemUriParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseClient.TermFallbackCache',
			$this->createMock( TermFallbackCacheFacade::class ) );
		$this->mockService( 'WikibaseClient.EntityLookup',
			new InMemoryEntityLookup() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLinkBatchFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageNameUtils' );
		$this->mockService( 'WikibaseClient.KartographerEmbeddingHandler',
			null );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'ThumbLimits' => [],
			] ) );

		$this->assertInstanceOf(
			WikibaseValueFormatterBuilders::class,
			$this->getService( 'WikibaseClient.DefaultValueFormatterBuilders' )
		);
	}

}
