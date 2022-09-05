<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Deserializers\Deserializer;
use HashSiteStore;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpDeserializerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$changeOpFactoryProvider = $this->createMock( ChangeOpFactoryProvider::class );
		$changeOpFactoryProvider->expects( $this->once() )
			->method( 'getFingerprintChangeOpFactory' )
			->willReturn( $this->createMock( FingerprintChangeOpFactory::class ) );
		$changeOpFactoryProvider->expects( $this->once() )
			->method( 'getStatementChangeOpFactory' )
			->willReturn( $this->createMock( StatementChangeOpFactory::class ) );
		$changeOpFactoryProvider->expects( $this->once() )
			->method( 'getSiteLinkChangeOpFactory' )
			->willReturn( $this->createMock( SiteLinkChangeOpFactory::class ) );
		$this->mockService( 'WikibaseRepo.ChangeOpFactoryProvider',
			$changeOpFactoryProvider );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'specialSiteLinkGroups' => [],
				'siteLinkGroups' => [],
			] ) );
		$this->mockService( 'WikibaseRepo.TermsLanguages',
			new StaticContentLanguages( [] ) );
		$this->mockService( 'WikibaseRepo.SiteLinkBadgeChangeOpSerializationValidator',
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ) );
		$this->mockService( 'WikibaseRepo.ExternalFormatStatementDeserializer',
			$this->createMock( Deserializer::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.EntityLookup',
			$this->createMock( EntityLookup::class ) );
		$this->mockService( 'WikibaseRepo.StringNormalizer',
			new StringNormalizer() );
		$this->mockService( 'WikibaseRepo.SiteLinkPageNormalizer',
			new SiteLinkPageNormalizer( [] ) );
		$this->mockService( 'WikibaseRepo.SiteLinkTargetProvider',
			new SiteLinkTargetProvider( new HashSiteStore() ) );

		$this->assertInstanceOf(
			ChangeOpDeserializerFactory::class,
			$this->getService( 'WikibaseRepo.ChangeOpDeserializerFactory' )
		);
	}

}
