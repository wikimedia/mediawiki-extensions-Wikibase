<?php

namespace Wikibase\Test;

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
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();

		$testUser = new TestUser( 'Wikibase User' );

		$parserOutputGenerator = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			new ParserOptions( $testUser->getUser(), Language::factory( 'en' ) )
		);

		$this->assertInstanceOf( 'Wikibase\EntityParserOutputGenerator', $parserOutputGenerator );
	}

	public function testGetEntityParserOutputGenerator_noParserOptionLanguage() {
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();

		$testUser = new TestUser( 'Wikibase User' );

		$parserOutputGenerator = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			new ParserOptions( $testUser->getUser() )
		);

		$this->assertInstanceOf( 'Wikibase\EntityParserOutputGenerator', $parserOutputGenerator );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory();
	}

}
