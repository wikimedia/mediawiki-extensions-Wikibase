<?php

namespace Wikibase;

use Language;
use ParserOptions;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\EntityParserOutputGeneratorFactory
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getEntityParserOutputGeneratorProvider
	 */
	public function testGetEntityParserOutputGenerator( $entityType ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$parserOutputGeneratorFactory = $wikibaseRepo->getEntityParserOutputGeneratorFactory();

		$parserOutputGenerator = $parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			$entityType,
			$this->getParserOptions()
		);

		$this->assertInstanceOf(
			'Wikibase\EntityParserOutputGenerator',
			$parserOutputGenerator
		);
	}

	public function getEntityParserOutputGeneratorProvider() {
		return array(
			array( 'item' ),
			array( 'property' )
		);
	}

	public function testGetEntityParserOutputGenerator_invalidType() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$parserOutputGeneratorFactory = $wikibaseRepo->getEntityParserOutputGeneratorFactory();

		$this->setExpectedException( 'InvalidArgumentException' );

		$parserOutputGeneratorFactory->getEntityParserOutputGenerator(
			'kittens',
			$this->getParserOptions()
		);
	}

	private function getParserOptions() {
		$testUser = new \TestUser( 'Wikibase User' );
		$language = Language::factory( 'en' );

		return new ParserOptions( $testUser->getUser(), $language );
	}

}
