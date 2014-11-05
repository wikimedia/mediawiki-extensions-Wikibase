<?php

namespace Wikibase;

use Language;
use ParserOptions;
use TestUser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactoryTest extends \MediaWikiTestCase {

	public function testGetEntityParserOutputGenerator() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$parserOutputGeneratorFactory = $wikibaseRepo->getEntityParserOutputGeneratorFactory();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q777' ) );
		$item->setLabel( 'en', 'elephant' );

		$entityRevision = new EntityRevision( $item, 7777 );

		$testUser = new TestUser( 'Wikibase User' );
		$language = Language::factory( 'en' );
		$parserOptions = new ParserOptions( $testUser->getUser(), $language );

		$parserOutputGenerator = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			$entityRevision,
			$parserOptions
		);

		$this->assertInstanceOf(
			'Wikibase\EntityParserOutputGenerator',
			$parserOutputGenerator
		);
	}

}
