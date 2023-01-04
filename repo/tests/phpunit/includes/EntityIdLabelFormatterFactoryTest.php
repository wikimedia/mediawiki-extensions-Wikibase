<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
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

	public function testGetFormat() {
		$lookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$formatterFactory = new EntityIdLabelFormatterFactory( $lookupFactory );

		$this->assertSame( SnakFormatter::FORMAT_PLAIN, $formatterFactory->getOutputFormat() );
	}

	public function testGetEntityIdFormatter() {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$lookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$lookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->with( $language );
		$formatterFactory = new EntityIdLabelFormatterFactory( $lookupFactory );

		$formatter = $formatterFactory->getEntityIdFormatter( $language );
		$this->assertInstanceOf( EntityIdFormatter::class, $formatter );
	}

}
