<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use DataValues\DataValue;
use DataValues\StringValue;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\Value
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValueTest extends TestCase {

	public function testConstructor(): void {
		$valueType = Value::TYPE_VALUE;
		$content = new StringValue( 'potato' );

		$value = new Value( $valueType, $content );

		$this->assertSame( $valueType, $value->getType() );
		$this->assertSame( $content, $value->getContent() );
	}

	/**
	 * @dataProvider invalidConstructorArgsProvider
	 */
	public function testGivenInvalidConstructorArgs_throws(
		string $valueType,
		?DataValue $content = null
	): void {
		$this->expectException( \InvalidArgumentException::class );
		$v = new Value( $valueType, $content );
	}

	public function invalidConstructorArgsProvider(): Generator {
		yield 'unknown value type' => [
			'not-a-valid-value-type',
		];

		yield 'type is "value" but no value provided' => [
			Value::TYPE_VALUE,
		];

		yield 'type is "novalue" but there is a value' => [
			Value::TYPE_NO_VALUE,
			new StringValue( 'potato' ),
		];
	}

	public function testGivenNonValue_getValueReturnsNull(): void {
		$value = new Value( Value::TYPE_NO_VALUE );

		$this->assertNull( $value->getContent() );
	}

}
