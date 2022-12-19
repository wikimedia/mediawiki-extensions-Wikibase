<?php
declare( strict_types=1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeFactoryTest extends ServiceWiringTestCase {
	public function testConstruction(): void {
		$mockDataTypeIds = [
			'some-property',
			'another-thing',
			'and-one-more',
		];

		$this->mockService( 'WikibaseClient.DataTypeDefinitions',
			new DataTypeDefinitions( array_fill_keys(
				array_map( function ( $dataTypeId ) {
					return 'PT:' . $dataTypeId;
				}, $mockDataTypeIds ),
				[ 'value-type' => 'string' ]
			) ) );

		$dataTypeFactory = $this->getService( 'WikibaseClient.DataTypeFactory' );

		$this->assertInstanceOf(
			DataTypeFactory::class,
			$dataTypeFactory
		);

		$this->assertEquals( $mockDataTypeIds, $dataTypeFactory->getTypeIds() );
	}

}
