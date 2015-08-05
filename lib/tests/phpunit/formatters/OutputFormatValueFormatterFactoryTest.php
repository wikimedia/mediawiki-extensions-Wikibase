<?php

namespace Wikibase\Lib\Test;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;

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
		$language = Language::factory( 'en' );
		$this->setExpectedException( $error );
		new OutputFormatValueFormatterFactory( $builder, $language );
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
		$language = Language::factory( 'en' );
		$factory = new OutputFormatValueFormatterFactory( $builders, $language );
		$formatter = $factory->getValueFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'ValueFormatters\ValueFormatter', $formatter );
	}

	public function getValueFormatterProvider() {
		$self = $this;
		$builders = array(
			'VT:foo' => function() use ( $self ) {
				return $self->makeMockValueFormatter( '<FOO>' );
			},
			'VT:bar' => function() use ( $self ) {
				return $self->makeMockValueFormatter( '<BAR>' );
			},
		);

		return array(
			'foo/plain' => array(
				$builders,
				SnakFormatter::FORMAT_PLAIN
			),
			'bar/html' => array(
				$builders,
				SnakFormatter::FORMAT_HTML
			),
		);
	}

}
