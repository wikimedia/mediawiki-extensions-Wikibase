<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultValueFormatterBuildersTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'ThumbLimits' => [],
			] ) );

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
				'geoShapeStorageBaseUrl' => '',
				'tabularDataStorageBaseUrl' => '',
				'sharedCacheDuration' => 0,
				'entitySchemaNamespace' => 0,
				'useKartographerMaplinkInWikitext' => false,
			] )
		);

		$this->mockService(
			'WikibaseRepo.TermLookup',
			$this->createMock( TermLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.RedirectResolvingLatestRevisionLookup',
			$this->createMock( RedirectResolvingLatestRevisionLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.LanguageNameLookup',
			$this->createMock( LanguageNameLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.ItemUrlParser',
			$this->createMock( SuffixEntityIdParser::class )
		);

		$this->mockService(
			'WikibaseRepo.TermFallbackCache',
			$this->createMock( TermFallbackCacheFacade::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityLookup',
			$this->createMock( EntityLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityExistenceChecker',
			$this->createMock( EntityExistenceChecker::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityTitleTextLookup',
			$this->createMock( EntityTitleTextLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityUrlLookup',
			$this->createMock( EntityUrlLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityRedirectChecker',
			$this->createMock( EntityRedirectChecker::class )
		);

		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );

		$this->mockService(
			'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.KartographerEmbeddingHandler',
			null
		);

		$this->assertInstanceOf(
			WikibaseValueFormatterBuilders::class,
			$this->getService( 'WikibaseRepo.DefaultValueFormatterBuilders' )
		);
	}
}
