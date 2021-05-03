<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [] )
		);

		$this->mockService(
			'WikibaseRepo.ValueFormatterFactory',
			$this->createMock( OutputFormatValueFormatterFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class )
		);
		$this->mockService(
			'WikibaseRepo.DataTypeFactory',
			$this->createMock( DataTypeFactory::class )
		);
		$this->mockService( 'WikibaseRepo.MessageInLanguageProvider',
			$this->createMock( MessageInLanguageProvider::class ) );

		$this->assertInstanceOf(
			OutputFormatSnakFormatterFactory::class,
			$this->getService( 'WikibaseRepo.SnakFormatterFactory' )
		);
	}

}
