<?php
namespace Wikibase\Lib\Test;

use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\SnakFormatterFactory;

/**
 * @covers Wikibase\Lib\SnakFormatterFactory
 *
 * @since 0.5
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 *
	 * @param $format
	 * @param $formatters
	 * @param $error
	 */
	public function testConstructorErrors( $builder, $error ) {
		$this->setExpectedException( $error );

		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->never() )->method( 'getDataTypeIdForProperty' );

		new SnakFormatterFactory( $builder );
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

	public function makeMockSnakFormatter( $format, $value ) {
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
	 * @dataProvider getFormatterProvider
	 * @covers SnakFormatterFactory::formatSnak()
	 */
	public function testGetFormatter( $builders, $format ) {
		$factory = new SnakFormatterFactory( $builders );
		$formatter = $factory->getFormatter( $format );

		$this->assertInstanceOf( 'Wikibase\Lib\SnakFormatter', $formatter );
		$this->assertEquals( $format, $formatter->getFormat() );
	}

	public function getFormatterProvider() {
		$this_ = $this;
		$builders = array(
			'foo' => function () use ( $this_ ) { return $this_->makeMockSnakFormatter( 'foo', 'FOO' ); },
			'bar' => function () use ( $this_ ) { return $this_->makeMockSnakFormatter( 'bar', 'BAR' ); },
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
