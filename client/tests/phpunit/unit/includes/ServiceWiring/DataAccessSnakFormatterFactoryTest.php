<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSnakFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);
		$this->mockService(
			'WikibaseClient.SnakFormatterFactory',
			$this->createMock( OutputFormatSnakFormatterFactory::class )
		);
		$this->mockService(
			'WikibaseClient.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class )
		);
		$this->mockService(
			'WikibaseClient.RepoItemUriParser',
			$this->createMock( EntityIdParser::class )
		);
		$this->mockService(
			'WikibaseClient.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class )
		);
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray(
				[ 'allowDataAccessInUserLanguage' => false ]
			)
		);
		$this->assertInstanceOf(
			DataAccessSnakFormatterFactory::class,
			$this->getService( 'WikibaseClient.DataAccessSnakFormatterFactory' )
		);
	}
}
