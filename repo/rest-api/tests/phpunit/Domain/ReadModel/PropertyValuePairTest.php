<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use DataValues\DataValue;
use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValuePairTest extends TestCase {

	public function testConstructor(): void {
		$propertyId = new NumericPropertyId( 'P321' );
		$dataType = 'string';
		$valueType = PropertyValuePair::TYPE_VALUE;
		$value = new StringValue( 'potato' );

		$propertyValuePair = new PropertyValuePair( $propertyId, $dataType, $valueType, $value );

		$this->assertSame( $propertyId, $propertyValuePair->getPropertyId() );
		$this->assertSame( $dataType, $propertyValuePair->getPropertyDataType() );
		$this->assertSame( $valueType, $propertyValuePair->getValueType() );
		$this->assertSame( $value, $propertyValuePair->getValue() );
	}

	/**
	 * @dataProvider invalidConstructorArgsProvider
	 */
	public function testGivenInvalidConstructorArgs_throws(
		string $valueType,
		?DataValue $value = null
	): void {
		$this->expectException( \InvalidArgumentException::class );
		$v = new PropertyValuePair( new NumericPropertyId( 'P123' ), 'string', $valueType, $value );
	}

	public function invalidConstructorArgsProvider(): Generator {
		yield 'unknown value type' => [
			'not-a-valid-value-type',
		];

		yield 'type is "value" but no value provided' => [
			PropertyValuePair::TYPE_VALUE,
		];

		yield 'type is "novalue" but there is a value' => [
			PropertyValuePair::TYPE_NO_VALUE,
			new StringValue( 'potato' ),
		];
	}

	public function testGivenNonValuePropertyValuePair_getValueReturnsNull(): void {
		$propertyValuePair = new PropertyValuePair(
			new NumericPropertyId( 'P666' ),
			'string',
			PropertyValuePair::TYPE_NO_VALUE
		);

		$this->assertNull( $propertyValuePair->getValue() );
	}

}
