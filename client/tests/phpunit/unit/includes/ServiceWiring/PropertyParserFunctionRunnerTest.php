<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\HashSiteLinkStore;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyParserFunctionRunnerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
				'allowArbitraryDataAccess' => false,
			] ) );
		$this->mockService( 'WikibaseClient.StatementGroupRendererFactory',
			$this->createMock( StatementGroupRendererFactory::class ) );
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->mockService( 'WikibaseClient.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseClient.RestrictedEntityLookup',
			$this->createMock( RestrictedEntityLookup::class ) );

		$this->assertInstanceOf(
			Runner::class,
			$this->getService( 'WikibaseClient.PropertyParserFunctionRunner' )
		);
	}

}
