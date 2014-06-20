<?php

namespace Wikibase\Lib\Test;

use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\Lib\OutputFormatValueFormatterFactory;

/**
 * @covers Wikibase\Lib\OutputFormatValueFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $builder, $error ) {
		$this->setExpectedException( $error );
		new OutputFormatValueFormatterFactory( $builder );
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

	public function makeMockValueFormatter( $value ) {
		$mock = $this->getMock( 'ValueFormatters\ValueFormatter' );

		$mock->expects( $this->atLeastOnce() )
			->method( 'format' )
			->will( $this->returnValue( $value ) );

		return $mock;
	}

	/**
	 * @dataProvider getValueFormatterProvider
	 */
	public function testGetValueFormatter( $builders, $format ) {
		$factory = new OutputFormatValueFormatterFactory( $builders );
		$formatter = $factory->getValueFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'ValueFormatters\ValueFormatter', $formatter );
	}

	public function getValueFormatterProvider() {
		$this_ = $this;
		$builders = array(
			'foo' => function () use ( $this_ ) { return $this_->makeMockValueFormatter( 'FOO' ); },
			'bar' => function () use ( $this_ ) { return $this_->makeMockValueFormatter( 'BAR' ); },
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
