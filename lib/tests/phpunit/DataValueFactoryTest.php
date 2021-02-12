<?php

namespace Wikibase\Lib\Tests;

use DataValues\IllegalValueException;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use Wikibase\Lib\DataValueFactory;

/**
 * @covers \Wikibase\Lib\DataValueFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DataValueFactoryTest extends \PHPUnit\Framework\TestCase {

	public function newInstance() {
		$deserializer = $this->createMock( Deserializer::class );
		$deserializer->method( 'deserialize' )
			->willReturnCallback( function( array $data ) {
				if ( $data['type'] === 'string' ) {
					return 'success';
				}
				throw new DeserializationException();
			} );

		return new DataValueFactory( $deserializer );
	}

	public function testGivenUnknownType_newDataValueFails() {
		$this->expectException( InvalidArgumentException::class );
		$this->newInstance()->newDataValue( 'unknown', '' );
	}

	public function testGivenKnownType_newDataValueSucceeds() {
		$value = $this->newInstance()->newDataValue( 'string', '' );
		$this->assertSame( 'success', $value );
	}

	public function testGivenNoType_newFromArrayFails() {
		$this->expectException( IllegalValueException::class );
		$this->newInstance()->newFromArray( [] );
	}

	public function testGivenNoValue_newFromArrayFails() {
		$this->expectException( IllegalValueException::class );
		$this->newInstance()->newFromArray( [ 'type' => 'unknown' ] );
	}

	public function testGivenUnknownType_newFromArrayFails() {
		$this->expectException( InvalidArgumentException::class );
		$this->newInstance()->newFromArray( [
			'type' => 'unknown',
			'value' => '',
		] );
	}

	public function testGivenKnownType_newFromArraySucceeds() {
		$value = $this->newInstance()->newFromArray( [
			'type' => 'string',
			'value' => '',
		] );
		$this->assertSame( 'success', $value );
	}

}
