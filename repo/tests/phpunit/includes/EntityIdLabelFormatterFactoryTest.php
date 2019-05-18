<?php

namespace Wikibase\Repo\Tests;

use Language;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\EntityIdLabelFormatterFactory;

/**
 * @covers \Wikibase\Repo\EntityIdLabelFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterFactoryTest extends TestCase {
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

		$formatter = $factory->getEntityIdFormatter( Language::factory( 'en' ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
	}

}
