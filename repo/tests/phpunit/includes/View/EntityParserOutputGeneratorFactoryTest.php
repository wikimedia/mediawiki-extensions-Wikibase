<?php

namespace Wikibase;

use IContextSource;
use Language;
use ParserOptions;
use ParserOutput;
use RequestContext;
use TestUser;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
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
