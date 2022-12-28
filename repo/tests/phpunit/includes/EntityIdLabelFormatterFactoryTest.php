<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\EntityIdLabelFormatterFactory;

/**
 * @covers \Wikibase\Repo\EntityIdLabelFormatterFactory
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatterFactoryTest extends TestCase {

	private function getFormatterFactory() {
		return new EntityIdLabelFormatterFactory();
	}

	public function testGetFormat() {
		$factory = $this->getFormatterFactory();

		$this->assertEquals( SnakFormatter::FORMAT_PLAIN, $factory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$factory = $this->getFormatterFactory();

		$formatter = $factory->getEntityIdFormatter( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ) );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
	}

}
