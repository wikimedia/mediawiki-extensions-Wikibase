<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$testDataType = 'entity-schema';
		$testDataValueType = 'string';
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [
				"PT:$testDataType" => [ 'value-type' => $testDataValueType ],
			] ) );

		/** @var $dataTypeFactory DataTypeFactory */
		$dataTypeFactory = $this->getService( 'WikibaseRepo.DataTypeFactory' );

		$this->assertInstanceOf( DataTypeFactory::class, $dataTypeFactory );
		$this->assertEquals(
			[
				$testDataType => new DataType( $testDataType, $testDataValueType ),
			],
			$dataTypeFactory->getTypes()
		);
	}

}
