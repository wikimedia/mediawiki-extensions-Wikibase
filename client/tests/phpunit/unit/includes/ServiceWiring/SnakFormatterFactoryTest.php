<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\MessageInLanguageProvider;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.DataTypeDefinitions',
			new DataTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseClient.ValueFormatterFactory',
			$this->createMock( OutputFormatValueFormatterFactory::class ) );
		$this->mockService( 'WikibaseClient.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseClient.DataTypeFactory',
			new DataTypeFactory( [] ) );
		$this->mockService( 'WikibaseClient.MessageInLanguageProvider',
			$this->createMock( MessageInLanguageProvider::class ) );

		$this->assertInstanceOf(
			OutputFormatSnakFormatterFactory::class,
			$this->getService( 'WikibaseClient.SnakFormatterFactory' )
		);
	}

}
