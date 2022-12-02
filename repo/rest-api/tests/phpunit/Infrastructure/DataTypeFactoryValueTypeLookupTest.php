<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeFactoryValueTypeLookupTest extends TestCase {

	/**
	 * @dataProvider dataTypeToExpectedValueTypeProvider
	 */
	public function testGetValueType( string $dataTypeId, string $expectedValueType ): void {
		$this->assertEquals(
			$expectedValueType,
			$this->newDataTypeFactoryValueTypeLookup()->getValueType( $dataTypeId )
		);
	}

	public function dataTypeToExpectedValueTypeProvider(): Generator {
		yield 'string' => [ 'string', 'string' ];
		yield 'url' => [ 'url', 'string' ];
		yield 'time' => [ 'time', 'foobar' ];
	}

	private function newDataTypeFactoryValueTypeLookup(): DataTypeFactoryValueTypeLookup {
		return new DataTypeFactoryValueTypeLookup(
			new DataTypeFactory( [ 'string' => 'string', 'url' => 'string', 'time' => 'foobar' ] )
		);
	}

}
