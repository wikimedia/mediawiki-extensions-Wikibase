<?php

namespace Wikibase\Lib\Test;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

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
	 * @dataProvider getSnakFormatterProvider
	 */
	public function testGetSnakFormatter( $builders, $format ) {
		$factory = new OutputFormatSnakFormatterFactory(
			$this->getWikibaseSnakFormatterBuilders( $builders )
		);

		$valueFormatterBuilders = $this->getValueFormatterBuilders();
		$formatter = $factory->getSnakFormatter(
			$format,
			$valueFormatterBuilders,
			new FormatterOptions()
		);

		$this->assertInstanceOf( 'Wikibase\Lib\SnakFormatter', $formatter );
		$this->assertEquals( $format, $formatter->getFormat() );
	}

	public function getSnakFormatterProvider() {
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

	private function getWikibaseSnakFormatterBuilders( array $builders ) {
		$snakFormatterBuilders = $this->getMockBuilder( 'Wikibase\Lib\WikibaseSnakFormatterBuilders' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterBuilders->expects( $this->any() )
			->method( 'getSnakFormatterBuildersForFormats' )
			->will( $this->returnValue( $builders ) );

		return $snakFormatterBuilders;
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

	private function getValueFormatterBuilders() {
		return new WikibaseValueFormatterBuilders(
			$this->getEntityLookup(),
			Language::factory( 'en' ),
			$this->getLabelLookup()
		);
	}

	private function getEntityLookup() {
		$itemId = new ItemId( 'Q5' );

		$item = Item::newEmpty();
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Label for ' . $itemId->getSerialization() );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $item ) );

		return $entityLookup;
	}

	private function getLabelLookup() {
		$labelLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\LabelLookup' )
			->disableOriginalConstructor()
			->getMock();

		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( 'Label for Q5' ) );

		return $labelLookup;
	}

}
