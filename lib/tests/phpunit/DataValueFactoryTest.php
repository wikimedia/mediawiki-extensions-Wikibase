<?php

namespace Wikibase\Lib\Test;

use DataValues\DataValueFactory;
use DataValues\IllegalValueException;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

/**
 * @covers DataValues\DataValueFactory
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class DataValueFactoryTest extends PHPUnit_Framework_TestCase {

	public function newInstance() {
		$deserializer = $this->getMock( Deserializer::class );
		$deserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnCallback( function( array $data ) {
				if ( $data['type'] === 'string' ) {
					return 'success';
				}
				throw new DeserializationException();
			} ) );

		return new DataValueFactory( $deserializer );
	}

	public function testGivenUnknownType_newDataValueFails() {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newInstance()->newDataValue( 'unknown', '' );
	}

	public function testGivenKnownType_newDataValueSucceeds() {
		$value = $this->newInstance()->newDataValue( 'string', '' );
		$this->assertSame( 'success', $value );
	}

	public function testGivenUnknownType_tryNewDataValueFails() {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newInstance()->tryNewDataValue( 'unknown', '' );
	}

	public function testGivenValidArguments_tryNewDataValueSucceeds() {
		$value = $this->newInstance()->tryNewDataValue( 'string', '' );
		$this->assertSame( 'success', $value );
	}

	public function testGivenNoType_newFromArrayFails() {
		$this->setExpectedException( IllegalValueException::class );
		$this->newInstance()->newFromArray( [] );
	}

	public function testGivenNoValue_newFromArrayFails() {
		$this->setExpectedException( IllegalValueException::class );
		$this->newInstance()->newFromArray( array( 'type' => 'unknown' ) );
	}

	public function testGivenUnknownType_newFromArrayFails() {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newInstance()->newFromArray( array(
			'type' => 'unknown',
			'value' => '',
		) );
	}

	public function testGivenKnownType_newFromArraySucceeds() {
		$value = $this->newInstance()->newFromArray( array(
			'type' => 'string',
			'value' => '',
		) );
		$this->assertSame( 'success', $value );
	}

	public function testGivenNoType_tryNewFromArrayReturnsWithError() {
		$value = $this->newInstance()->tryNewFromArray( [] );
		$expected = new UnDeserializableValue( null, null, 'No type specified' );
		$this->assertEquals( $expected, $value );
	}

	public function testGivenNoValue_tryNewFromArrayReturnsWithError() {
		$value = $this->newInstance()->tryNewFromArray( array( 'type' => 'unknown' ) );
		$expected = new UnDeserializableValue( null, 'unknown', 'No value data' );
		$this->assertEquals( $expected, $value );
	}

	public function testGivenUnknownType_tryNewFromArrayFails() {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newInstance()->tryNewFromArray( array(
			'type' => 'unknown',
			'value' => '',
		) );
	}

	public function testGivenKnownType_tryNewFromArraySucceeds() {
		$value = $this->newInstance()->tryNewFromArray( array(
			'type' => 'string',
			'value' => '',
		) );
		$this->assertSame( 'success', $value );
	}

}
