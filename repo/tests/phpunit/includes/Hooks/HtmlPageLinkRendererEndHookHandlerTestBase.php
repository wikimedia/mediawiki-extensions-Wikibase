<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler;

/**
 * @license GPL-2.0-or-later
 */
abstract class HtmlPageLinkRendererEndHookHandlerTestBase extends MediaWikiIntegrationTestCase {

	const ITEM_WITH_LABEL = 'Q1';
	const ITEM_WITHOUT_LABEL = 'Q11';
	const ITEM_DELETED = 'Q111';
	const ITEM_LABEL_NO_DESCRIPTION = 'Q1111';
	const ITEM_FOREIGN = 'foo:Q2';
	const ITEM_FOREIGN_NO_DATA = 'foo:Q22';
	const ITEM_FOREIGN_NO_PREFIX = 'Q2';
	const ITEM_FOREIGN_NO_DATA_NO_PREFIX = 'Q22';
	const PROPERTY_WITH_LABEL = 'P1';

	const FOREIGN_REPO_PREFIX = 'foo';
	const UNKNOWN_FOREIGN_REPO = 'bar';

	const DUMMY_LABEL = 'linkbegin-label';
	const DUMMY_LABEL_FOREIGN_ITEM = 'linkbegin-foreign-item-label';

	const DUMMY_DESCRIPTION = 'linkbegin-description';
	const DUMMY_DESCRIPTION_FOREIGN_ITEM = 'linkbegin-foreign-item-description';

	protected function setUp(): void {
		parent::setUp();
		$this->entityUrlLookup = $this->createMock( EntityUrlLookup::class );
	}

	/**
	 * @param string $id
	 * @param bool $exists
	 *
	 * @return Title
	 */
	protected function newTitle( $id, $exists = true ) {
		$title = Title::makeTitle( NS_MAIN, $id );
		$title->resetArticleID( $exists ? 1 : 0 );
		$this->assertSame( $exists, $title->exists(), 'Sanity check' );
		return $title;
	}

	protected function newContext( string $title = 'Special:Recentchanges' ): RequestContext {
		return RequestContext::newExtraneousContext( Title::newFromText( $title ) );
	}

	protected function getLinkRenderer(): LinkRenderer {
		return MediaWikiServices::getInstance()->getLinkRenderer();
	}

	protected function newInstance(
		$titleText = "foo",
		$isDeleted = false,
		$federatedPropertiesEnabled = false,
		$entityType = Item::ENTITY_TYPE
	) {
		$languageFallback = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		] );
		$languageFallbackChainFactory = $this
			->createMock( LanguageFallbackChainFactory::class );

		$languageFallbackChainFactory->expects( $this->any() )
			->method( 'newFromContext' )
			->willReturn( $languageFallback );
		$entityIdParser = new BasicEntityIdParser();
		return new HtmlPageLinkRendererEndHookHandler(
			$this->getEntityExistenceChecker( $isDeleted ),
			$entityIdParser,
			$this->getTermLookup(),
			$this->getEntityNamespaceLookup(),
			$this->getInterwikiLookup(),
			$this->getEntityLinkFormatterFactory( $titleText ),
			MediaWikiServices::getInstance()->getSpecialPageFactory(),
			$languageFallbackChainFactory,
			$this->entityUrlLookup,
			new EntityLinkTargetEntityIdLookup(
				$this->getEntityNamespaceLookup(),
				$entityIdParser,
				$this->newMockEntitySourceDefinitions( $entityType ),
				$this->newMockEntitySource()
			),
			'http://source.wiki/script/',
			$federatedPropertiesEnabled
		);
	}

	private function getEntityLinkFormatterFactory( $titleText ) {
		$titleTextLookup = $this->getEntityTitleTextLookup( $titleText );

		return new EntityLinkFormatterFactory( Language::factory( 'en' ), $titleTextLookup, [
			'item' => function( $language ) use ( $titleTextLookup ) {
				return new DefaultEntityLinkFormatter( $language, $titleTextLookup );
			},
			'property' => function( $language ) use ( $titleTextLookup ) {
				return new DefaultEntityLinkFormatter( $language, $titleTextLookup );
			},
		] );
	}

	private function getEntityExistenceChecker( $isDeleted ) {
		$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );

		$entityExistenceChecker->expects( $this->any() )
			->method( 'exists' )
			->willReturn( !$isDeleted );
		return $entityExistenceChecker;
	}

	private function getEntityTitleTextLookup( $titleText ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );

		$entityTitleTextLookup->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $titleText );

		return $entityTitleTextLookup;
	}

	private function newMockEntitySourceDefinitions( $entityType ) {
		$foreignItemSource = $this->createMock( EntitySource::class );
		$foreignItemSource->expects( $this->any() )
			->method( 'getInterwikiPrefix' )
			->willReturn( self::FOREIGN_REPO_PREFIX );

		$sourceDefs = $this->createMock( EntitySourceDefinitions::class );
		$sourceDefs->expects( $this->any() )
			->method( 'getSourceForEntityType' )
			->with( $entityType )
			->willReturn( $foreignItemSource );

		return $sourceDefs;
	}

	private function newMockEntitySource() {
		$entitySource = $this->createMock( EntitySource::class );
		$entitySource->expects( $this->any() )
			->method( 'getEntityTypes' )
			->willReturn( [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] );

		return $entitySource;
	}

	/**
	 * @return TermLookup
	 */
	private function getTermLookup() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				switch ( $id->getSerialization() ) {
					case self::ITEM_WITH_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [ 'en' => self::DUMMY_LABEL ];
					case self::ITEM_WITHOUT_LABEL:
						return [];
					case self::ITEM_FOREIGN_NO_PREFIX:
						return [ 'en' => self::DUMMY_LABEL_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA_NO_PREFIX:
						return [];
					case self::PROPERTY_WITH_LABEL:
						return [ 'en' => self::DUMMY_LABEL ];
					default:
						throw new StorageException( "Unexpected entity id $id" );
				}
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				switch ( $id->getSerialization() ) {
					case self::ITEM_WITH_LABEL:
						return [ 'en' => self::DUMMY_DESCRIPTION ];
					case self::ITEM_WITHOUT_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [];
					case self::ITEM_FOREIGN_NO_PREFIX:
						return [ 'en' => self::DUMMY_DESCRIPTION_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA_NO_PREFIX:
						return [];
					case self::PROPERTY_WITH_LABEL:
						return [];
					default:
						throw new StorageException( "Unexpected entity id $id" );
				}
			} ) );

		return $termLookup;
	}

	private function getEntityNamespaceLookup() {
		$entityNamespaces = [
			'item' => 0,
			'property' => 122
		];

		return new EntityNamespaceLookup( $entityNamespaces );
	}

	private function getInterwikiLookup() {
		$lookup = $this->createMock( InterwikiLookup::class );
		$lookup->expects( $this->any() )
			->method( 'isValidInterwiki' )
			->will(
				$this->returnCallback( function( $interwiki ) {
					return $interwiki === self::FOREIGN_REPO_PREFIX;
				} )
			);
		return $lookup;
	}

	public function validContextProvider() {
		$historyContext = $this->newContext( 'Foo' );
		$historyContext->getRequest()->setVal( 'action', 'history' );

		$diffContext = $this->newContext( 'Foo' );
		$diffContext->getRequest()->setVal( 'diff', 123 );

		return [
			'Special page' => [ $this->newContext() ],
			'Action history' => [ $historyContext ],
			'Diff' => [ $diffContext ],
		];
	}

	public function invalidContextProvider() {
		$deleteContext = $this->newContext( 'Foo' );
		$deleteContext->getRequest()->setVal( 'action', 'delete' );

		$diffNonViewContext = $this->newContext( 'Foo' );
		$diffNonViewContext->getRequest()->setVal( 'action', 'protect' );
		$diffNonViewContext->getRequest()->setVal( 'diff', 123 );

		return [
			'Action delete' => [ $deleteContext ],
			'Non-special page' => [ $this->newContext( 'Foo' ) ],
			'Edge case: diff parameter set, but action != view' => [ $diffNonViewContext ],
		];
	}

}
