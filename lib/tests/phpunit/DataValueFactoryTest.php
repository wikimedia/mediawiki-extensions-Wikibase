<?php

namespace Wikibase\Lib\Test;

use DataValues\DataValueFactory;
use DataValues\IllegalValueException;
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

	public function testGivenNoType_newFromArrayFails() {
		$this->setExpectedException( IllegalValueException::class );
		$this->newInstance()->newFromArray( array() );
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

}
