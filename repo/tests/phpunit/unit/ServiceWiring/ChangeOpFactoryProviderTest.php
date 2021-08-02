<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashSiteStore;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFactoryProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.DataTypeFactory',
			new DataTypeFactory( [] ) );
		$this->mockService( 'WikibaseRepo.DataTypeValidatorFactory',
			$this->createMock( DataTypeValidatorFactory::class ) );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'badgeItems' => [],
				'tmpNormalizeDataValues' => true,
			] ) );
		$this->mockService( 'WikibaseRepo.EntityConstraintProvider',
			$this->createMock( EntityConstraintProvider::class ) );
		$entityIdParser = new ItemIdParser();
		$this->mockService( 'WikibaseRepo.StatementGuidValidator',
			new StatementGuidValidator( $entityIdParser ) );
		$this->mockService( 'WikibaseRepo.StatementGuidParser',
			new StatementGuidParser( $entityIdParser ) );
		$this->mockService( 'WikibaseRepo.TermValidatorFactory',
			$this->createMock( TermValidatorFactory::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore() );
		$this->mockService( 'WikibaseRepo.SnakNormalizer',
			$this->createMock( SnakNormalizer::class ) );
		$this->mockService( 'WikibaseRepo.ReferenceNormalizer',
			$this->createMock( ReferenceNormalizer::class ) );
		$this->mockService( 'WikibaseRepo.StatementNormalizer',
			$this->createMock( StatementNormalizer::class ) );

		$this->assertInstanceOf(
			ChangeOpFactoryProvider::class,
			$this->getService( 'WikibaseRepo.ChangeOpFactoryProvider' )
		);
	}

}
