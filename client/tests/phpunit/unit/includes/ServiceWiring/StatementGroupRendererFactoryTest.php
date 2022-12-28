<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementGroupRendererFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.PropertyLabelResolver',
			$this->createMock( PropertyLabelResolver::class ) );
		$this->mockService( 'WikibaseClient.RestrictedEntityLookup',
			$this->createMock( RestrictedEntityLookup::class ) );
		$this->mockService( 'WikibaseClient.DataAccessSnakFormatterFactory',
			$this->createMock( DataAccessSnakFormatterFactory::class ) );
		$this->mockService( 'WikibaseClient.UsageAccumulatorFactory', $this->createMock( UsageAccumulatorFactory::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageConverterFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'allowDataAccessInUserLanguage' => false,
			] ) );

		$this->assertInstanceOf(
			StatementGroupRendererFactory::class,
			$this->getService( 'WikibaseClient.StatementGroupRendererFactory' )
		);
	}

}
