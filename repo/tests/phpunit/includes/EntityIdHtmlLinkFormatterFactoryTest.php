<?php

namespace Wikibase\Repo\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;

/**
 * @covers Wikibase\Repo\EntityIdHtmlLinkFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getFormatterFactory() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		return new EntityIdHtmlLinkFormatterFactory(
			$titleLookup,
			$languageNameLookup
		);
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_HTML, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormatter( $this->getMock( LabelDescriptionLookup::class ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
	}

}
