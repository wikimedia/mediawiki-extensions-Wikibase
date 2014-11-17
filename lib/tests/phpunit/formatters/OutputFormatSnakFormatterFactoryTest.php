<?php

namespace Wikibase\Lib\Test;

use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\OutputFormatSnakFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class OutputFormatSnakFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 * @param array $builders
	 * @param string $error
	 */
	public function testConstructorErrors( array $builders, $error ) {
		$this->setExpectedException( $error );

		$typeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		new OutputFormatSnakFormatterFactory( $builders );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return array(
			'keys must be strings' => array(
				array( 17 => $stringFormatter ),
				'InvalidArgumentException'
			),
			'builder must be callable' => array(
				array( 'foo' => 17 ),
				'InvalidArgumentException'
			),
		);
	}

	/**
	 * @param string $format
	 * @param string $value
	 *
	 * @return SnakFormatter
	 */
	public function getSnakFormatter( $format, $value ) {
		$mock = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$mock->expects( $this->atLeastOnce() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $value ) );

		$mock->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );

		return $mock;
	}

	/**
	 * @dataProvider getSnakFormatterProvider
	 * @param callable[] $builders
	 * @param string $format
	 */
	public function testGetSnakFormatter( array $builders, $format ) {
		$factory = new OutputFormatSnakFormatterFactory( $builders );
		$formatter = $factory->getSnakFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'Wikibase\Lib\SnakFormatter', $formatter );
		$this->assertEquals( $format, $formatter->getFormat() );
	}

	public function getSnakFormatterProvider() {
		$self = $this;
		$builders = array(
			'foo' => function() use ( $self ) { return $self->getSnakFormatter( 'foo', 'FOO' ); },
			'bar' => function() use ( $self ) { return $self->getSnakFormatter( 'bar', 'BAR' ); },
		);

		return array(
			'foo' => array(
				$builders,
				'foo'
			),
			'bar' => array(
				$builders,
				'bar'
			),
		);
	}

}
