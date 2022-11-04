<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Store;

use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Lib\Tests\FakeCache;

/**
 * @covers \Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class FallbackLabelDescriptionLookupFactoryTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( MainConfigNames::UsePigLatinVariant, false );
	}

	public function testNewLabelDescriptionLookup(): void {
		$sourceId = new ItemId( 'Q2' );
		$targetId = new ItemId( 'Q1' );
		$revisionLookup = $this->createMock( RedirectResolvingLatestRevisionLookup::class );
		$revisionLookup->expects( $this->once() )
			->method( 'lookupLatestRevisionResolvingRedirect' )
			->with( $sourceId )
			->willReturn( [ 123, $targetId ] );
		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $targetId, [ 'en' ] )
			->willReturn( [ 'en' => 'label' ] );
		$factory = new FallbackLabelDescriptionLookupFactory(
			new LanguageFallbackChainFactory(),
			$revisionLookup,
			new TermFallbackCacheFacade( new FakeCache(), 10 ),
			$termLookup
		);

		$label = $factory->newLabelDescriptionLookup(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		)->getLabel( $sourceId );

		$this->assertNotNull( $label );
		$this->assertSame( 'label', $label->getText() );
		$this->assertSame( 'en', $label->getLanguageCode() );
	}

	public function testNewLabelDescriptionLookup_callsPrefetch(): void {
		$entityIds = [ new ItemId( 'Q1' ), new NumericPropertyId( 'P2' ) ];
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ];
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with( $entityIds, $termTypes, [ 'en' ] );
		$factory = new FallbackLabelDescriptionLookupFactory(
			new LanguageFallbackChainFactory(),
			$this->createMock( RedirectResolvingLatestRevisionLookup::class ),
			new TermFallbackCacheFacade( new FakeCache(), 10 ),
			$this->createMock( TermLookup::class ),
			$termBuffer
		);

		$factory->newLabelDescriptionLookup(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ),
			$entityIds,
			$termTypes
		);
	}

	public function testNewLabelDescriptionLookup_noPrefetchWithoutEntityIds(): void {
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->never() )
			->method( 'prefetchTerms' );
		$languageFallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$languageFallbackChain->expects( $this->never() )
			->method( 'getFetchLanguageCodes' );
		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->method( 'newFromLanguage' )
			->willReturn( $languageFallbackChain );
		$factory = new FallbackLabelDescriptionLookupFactory(
			$languageFallbackChainFactory,
			$this->createMock( RedirectResolvingLatestRevisionLookup::class ),
			new TermFallbackCacheFacade( new FakeCache(), 10 ),
			$this->createMock( TermLookup::class ),
			$termBuffer
		);

		$factory->newLabelDescriptionLookup(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

}
