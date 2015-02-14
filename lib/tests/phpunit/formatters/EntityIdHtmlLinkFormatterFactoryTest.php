<?php

namespace Wikibase\Lib\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\Lib\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\EntityIdHtmlLinkFormatterFactory
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getFormatterFactory() {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );

		$labelLookupFactory = $this->getMockBuilder( 'Wikibase\Lib\FormatterLabelLookupFactory' )
			->disableOriginalConstructor()
			->getMock();

		$labelLookupFactory->expects( $this->any() )
			->method( 'getLabelLookup' )
			->will( $this->returnValue( $labelLookup ) );

		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		return new EntityIdHtmlLinkFormatterFactory(
			$labelLookupFactory,
			$titleLookup,
			$this->getMock( 'Wikibase\Lib\LanguageNameLookup' )
		);
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_HTML, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormater( new FormatterOptions() );
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdFormatter', $formatter );
	}

}
