<?php

namespace Wikibase;

use Language;
use ParserOptions;
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

		$testUser = new \TestUser( 'Wikibase User' );
		$language = Language::factory( 'en' );
		$parserOptions = new ParserOptions( $testUser->getUser(), $language );

		$parserOutputGenerator = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			'item',
			$parserOptions
		);

		$this->assertInstanceOf(
			'Wikibase\EntityParserOutputGenerator',
			$parserOutputGenerator
		);
	}

}
