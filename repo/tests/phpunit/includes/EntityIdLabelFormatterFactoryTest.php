<?php

namespace Wikibase\Repo\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntityIdLabelFormatterFactory;

/**
 * @covers Wikibase\Repo\EntityIdLabelFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getFormatterFactory() {
		return new EntityIdLabelFormatterFactory();
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_PLAIN, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormatter( $this->getMock( LabelDescriptionLookup::class ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
	}

}
