<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler;

/**
 * @license GPL-2.0-or-later
 */
abstract class HtmlPageLinkRendererEndHookHandlerTestBase extends MediaWikiLangTestCase {

	protected const ITEM_WITH_LABEL = 'Q1';
	protected const ITEM_WITHOUT_LABEL = 'Q11';
	protected const ITEM_DELETED = 'Q111';
	protected const ITEM_LABEL_NO_DESCRIPTION = 'Q1111';
	protected const ITEM_FOREIGN = 'foo:Q2';
	protected const ITEM_FOREIGN_NO_DATA = 'foo:Q22';
	protected const ITEM_FOREIGN_NO_PREFIX = 'Q2';
	protected const ITEM_FOREIGN_NO_DATA_NO_PREFIX = 'Q22';
	protected const PROPERTY_WITH_LABEL = 'P1';

	protected const FOREIGN_REPO_PREFIX = 'foo';
	protected const UNKNOWN_FOREIGN_REPO = 'bar';

	protected const DUMMY_LABEL = 'linkbegin-label';
	protected const DUMMY_LABEL_FOREIGN_ITEM = 'linkbegin-foreign-item-label';

	protected const DUMMY_DESCRIPTION = 'linkbegin-description';
	protected const DUMMY_DESCRIPTION_FOREIGN_ITEM = 'linkbegin-foreign-item-description';

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
		return RequestContext::newExtraneousContext( Title::newFromTextThrow( $title ) );
	}

	protected function getLinkRenderer(): LinkRenderer {
		$linkRenderer = MediaWikiServices::getInstance()
			->getLinkRendererFactory()->create( [ 'renderForComment' => true ] );
		return $linkRenderer;
	}

	protected function newInstance(
		$titleText = "foo",
		$isDeleted = false,
		$federatedPropertiesEnabled = false,
		$entityType = Item::ENTITY_TYPE
	) {
		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )
			->willReturn( true );
		$languageFallback = new TermLanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		], $stubContentLanguages );
		$languageFallbackChainFactory = $this
			->createMock( LanguageFallbackChainFactory::class );

		$languageFallbackChainFactory->method( 'newFromContext' )
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
		$languageFactory = $this->getServiceContainer()->getLanguageFactory();

		return new EntityLinkFormatterFactory( $titleTextLookup, $languageFactory, [
			'item' => function( $language ) use ( $titleTextLookup, $languageFactory ) {
				return new DefaultEntityLinkFormatter( $language, $titleTextLookup, $languageFactory );
			},
			'property' => function( $language ) use ( $titleTextLookup, $languageFactory ) {
				return new DefaultEntityLinkFormatter( $language, $titleTextLookup, $languageFactory );
			},
		] );
	}

	private function getEntityExistenceChecker( $isDeleted ) {
		$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );

		$entityExistenceChecker->method( 'exists' )
			->willReturn( !$isDeleted );
		return $entityExistenceChecker;
	}

	private function getEntityTitleTextLookup( $titleText ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );

		$entityTitleTextLookup->method( 'getPrefixedText' )
			->willReturn( $titleText );

		return $entityTitleTextLookup;
	}

	private function newMockEntitySourceDefinitions( $entityType ) {
		$foreignItemSource = $this->createMock( DatabaseEntitySource::class );
		$foreignItemSource->method( 'getInterwikiPrefix' )
			->willReturn( self::FOREIGN_REPO_PREFIX );

		$sourceDefs = $this->createMock( EntitySourceDefinitions::class );
		$sourceDefs->method( 'getDatabaseSourceForEntityType' )
			->with( $entityType )
			->willReturn( $foreignItemSource );

		return $sourceDefs;
	}

	private function newMockEntitySource() {
		$entitySource = $this->createMock( DatabaseEntitySource::class );
		$entitySource->method( 'getEntityTypes' )
			->willReturn( [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] );

		return $entitySource;
	}

	/**
	 * @return TermLookup
	 */
	private function getTermLookup() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->method( 'getLabels' )
			->willReturnCallback( function ( EntityId $id ) {
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
			} );

		$termLookup->method( 'getDescriptions' )
			->willReturnCallback( function ( EntityId $id ) {
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
			} );

		return $termLookup;
	}

	final protected function getEntityNamespaceLookup() {
		$entityNamespaces = [
			'item' => 0,
			'property' => 122,
			// needed for HtmlPageLinkRendererEndHookHandlerTest::overrideSpecialNewEntityLinkProvider
			// when tests run with WikibaseLexeme installed
			'lexeme' => 146,
		];

		return new EntityNamespaceLookup( $entityNamespaces );
	}

	private function getInterwikiLookup() {
		$lookup = $this->createMock( InterwikiLookup::class );
		$lookup->method( 'isValidInterwiki' )
			->willReturnCallback( function( $interwiki ) {
				return $interwiki === self::FOREIGN_REPO_PREFIX;
			} );
		return $lookup;
	}

	public function validLinkRendererAndContextProvider() {
		$commentLinkRenderer = $this->getLinkRenderer();
		$nonCommentLinkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$specialPageContext = $this->newContext();

		$historyContext = $this->newContext( 'Foo' );
		$historyContext->getRequest()->setVal( 'action', 'history' );

		$diffContext = $this->newContext( 'Foo' );
		$diffContext->getRequest()->setVal( 'diff', 123 );

		$viewContext = $this->newContext( 'Foo' );

		return [
			'normal link, special page' => [ $nonCommentLinkRenderer, $specialPageContext ],
			'comment link, history' => [ $commentLinkRenderer, $historyContext ],
			'comment link, diff view' => [ $commentLinkRenderer, $diffContext ],
			'comment link, normal view' => [ $commentLinkRenderer, $viewContext ],
			'comment link, special page' => [ $commentLinkRenderer, $specialPageContext ],
		];
	}

	public function invalidLinkRendererAndContextProvider() {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();

		$viewContext = $this->newContext( 'Foo' );

		$diffContext = $this->newContext( 'Foo' );
		$diffContext->getRequest()->setVal( 'diff', 123 );

		return [
			'normal link, normal view' => [ $linkRenderer, $viewContext ],
			'normal link, diff view' => [ $linkRenderer, $diffContext ],
		];
	}

}
