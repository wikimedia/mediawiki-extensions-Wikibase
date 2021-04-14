<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\ValidatorBuilders;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultValidatorBuildersTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'urlSchemes' => [],
				'geoShapeStorageApiEndpointUrl' => '',
				'tabularDataStorageApiEndpointUrl' => '',
			] ) );
		$this->mockService( 'WikibaseRepo.EntityLookup',
			new InMemoryEntityLookup() );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.ItemVocabularyBaseUri',
			'' );
		$this->mockService( 'WikibaseRepo.MonolingualTextLanguages',
			new StaticContentLanguages( [] ) );
		$this->mockService( 'WikibaseRepo.CachingCommonsMediaFileNameLookup',
			$this->createMock( CachingCommonsMediaFileNameLookup::class ) );

		$this->assertInstanceOf(
			ValidatorBuilders::class,
			$this->getService( 'WikibaseRepo.DefaultValidatorBuilders' )
		);
	}

}
