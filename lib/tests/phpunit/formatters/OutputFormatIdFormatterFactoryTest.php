<?php

namespace Wikibase\Lib\Test;

use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\OutputFormatIdFormatterFactory;

/**
 * @covers Wikibase\Lib\OutputFormatIdFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class OutputFormatIdFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $builder, $error ) {
		$this->setExpectedException( $error );
		new OutputFormatIdFormatterFactory( $builder );
	}

	public function constructorErrorsProvider() {
		$idFormatter = new EntityIdFormatter( new FormatterOptions() );

		return array(
			'keys must be strings' => array(
				array( 17 => $idFormatter ),
				'InvalidArgumentException'
			),
			'builder must be callable' => array(
				array( 'foo' => 17 ),
				'InvalidArgumentException'
			),
		);
	}

	public function makeMockIdFormatter( $value ) {
		$mock = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->atLeastOnce() )
			->method( 'format' )
			->will( $this->returnValue( $value ) );

		return $mock;
	}

	/**
	 * @dataProvider getIdFormatterProvider
	 */
	public function testGetIdFormatter( $builders, $format ) {
		$factory = new OutputFormatIdFormatterFactory( $builders );
		$formatter = $factory->getIdFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdFormatter', $formatter );
	}

	public function getIdFormatterProvider() {
		$this_ = $this;
		$builders = array(
			'foo' => function () use ( $this_ ) { return $this_->makeMockIdFormatter( 'FOO' ); },
			'bar' => function () use ( $this_ ) { return $this_->makeMockIdFormatter( 'BAR' ); },
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
