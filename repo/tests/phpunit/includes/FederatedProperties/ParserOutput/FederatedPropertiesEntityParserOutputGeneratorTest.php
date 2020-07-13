<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\ParserOutput;

use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\FederatedProperties\ApiRequestExecutionException;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityParserOutputGenerator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesError;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ItemParserOutputUpdater;
use Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityParserOutputGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesEntityParserOutputGeneratorTest extends MediaWikiTestCase {

	/**
	 * @dataProvider errorPageProvider
	 */
	public function testGetParserOutputHandlesFederatedApiException( $labelLanguage, $userLanguage ) {

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setLabel( $labelLanguage, 'kitten item' );

		$updater = $this->createMock( ItemParserOutputUpdater::class );
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator( [ $updater ], $userLanguage );
		$updater->method( 'updateParserOutput' )
			->willThrowException( new ApiRequestExecutionException() );

		// T254888 Exception will be handled and show an error page.
		$this->expectException( FederatedPropertiesError::class );

		$entityParserOutputGenerator->getParserOutput( new EntityRevision( $item, 4711 ), false );
	}

	private function newEntityParserOutputGenerator( $dataUpdaters = null, $languageCode = 'en' ) {

		$language = Language::factory( $languageCode );

		$fullGenerator = new FullEntityParserOutputGenerator(
			$this->createMock( DispatchingEntityViewFactory::class ),
			$this->createMock( DispatchingEntityMetaTagsCreatorFactory::class ),
			$this->createMock( ParserOutputJsConfigBuilder::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( LanguageFallbackChain::class ),
			TemplateFactory::getDefaultInstance(),
			$this->createMock( LocalizedTextProvider::class ),
			new EntityDataFormatProvider(),
			$dataUpdaters,
			$language
		);

		return new FederatedPropertiesEntityParserOutputGenerator( $fullGenerator, $language );
	}

	public function errorPageProvider() {
		return [
			[ 'en', 'en' ],
			[ 'de', 'en' ],
		];
	}
}
