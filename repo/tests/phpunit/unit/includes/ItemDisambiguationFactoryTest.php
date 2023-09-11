<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use Language;
use MediaWikiUnitTestCase;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ItemDisambiguation;
use Wikibase\Repo\ItemDisambiguationFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\SiteLinkPageNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDisambiguationFactoryTest extends MediaWikiUnitTestCase {

	public function testGetForLanguage(): void {
		$entityTitleLookup = $this->createStub( EntityTitleLookup::class );
		$languageNameLookup = $this->createStub( LanguageNameLookup::class );
		$language = $this->createStub( Language::class );
		$languageNameLookupFactory = $this->createMock( LanguageNameLookupFactory::class );
		$languageNameLookupFactory->expects( $this->once() )
			->method( 'getForLanguage' )
			->with( $language )
			->willReturn( $languageNameLookup );
		$itemDisambiguationFactory = new ItemDisambiguationFactory(
			$entityTitleLookup,
			$languageNameLookupFactory
		);

		$itemDisambiguation = $itemDisambiguationFactory->getForLanguage( $language );

		$this->assertInstanceOf( ItemDisambiguation::class, $itemDisambiguation );
		$itemDisambiguation = TestingAccessWrapper::newFromObject( $itemDisambiguation );
		$this->assertSame( $entityTitleLookup, $itemDisambiguation->titleLookup );
		$this->assertSame( $languageNameLookup, $itemDisambiguation->languageNameLookup );
	}

}
