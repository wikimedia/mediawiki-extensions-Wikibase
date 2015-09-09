<?php

namespace Wikibase\Repo\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;

/**
 * @covers Wikibase\Repo\EntityIdHtmlLinkFormatterFactory
 *
 * @group ValueFormatters
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactoryTest extends PHPUnit_Framework_TestCase {

	private function getFormatterFactory() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		return new EntityIdHtmlLinkFormatterFactory(
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

		$formatter = $factory->getEntityIdFormatter( $this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Services\EntityId\EntityIdFormatter', $formatter );
	}

}
