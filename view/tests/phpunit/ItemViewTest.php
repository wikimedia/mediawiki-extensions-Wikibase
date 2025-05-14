<?php

namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\ItemView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SiteLinksView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\View\ItemView
 * @covers \Wikibase\View\EntityView
 *
 * @uses \Wikibase\View\Template\Template
 * @uses \Wikibase\View\Template\TemplateFactory
 * @uses \Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ItemViewTest extends EntityViewTestCase {

	/**
	 * @param EntityId|ItemId $id
	 * @param Statement[] $statements
	 *
	 * @return Item
	 */
	protected static function makeEntity( EntityId $id, array $statements = [] ) {
		$item = new Item( $id );

		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		$item->setStatements( new StatementList( ...$statements ) );

		return $item;
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return ItemId
	 */
	protected static function makeEntityId( $n ) {
		return new ItemId( "Q$n" );
	}

	public static function provideTestGetHtml() {
		return [
			[
				fn ( self $self ) => $self->newItemView(),
				self::newEntityForStatements( [] ),
				'/wb-item/',
			],
		];
	}

	public static function provideTestVueStatementsView(): iterable {
		return [
			[
				'viewFactory' => fn ( self $self ) => $self->newItemView(),
				'item' => self::newEntityForStatements( [] ),
				'vueStatementsExpected' => false,
			],
			[
				'viewFactory' => fn ( self $self ) => $self->newItemView( [], true ),
				'item' => self::newEntityForStatements( [] ),
				'vueStatementsExpected' => true,
			],
		];
	}

	/** @dataProvider provideTestVueStatementsView */
	public function testVueStatementsView( callable $viewFactory, Item $item, bool $vueStatementsExpected ) {
		$view = $viewFactory( $this );
		$output = $view->getContent( $item, null );
		$html = $output->getHtml();

		if ( $vueStatementsExpected ) {
			$this->assertStringContainsString( 'mobile-ui-statements-view-placeholder', $html );
		} else {
			$this->assertStringNotContainsString( 'mobile-ui-statements-view-placeholder', $html );
		}
	}

	public function testTermsViewPlaceholdersArePropagated() {
		$placeholders = [ 'a' => 'b' ];
		$itemView = $this->newItemView( $placeholders );

		$view = $itemView->getContent( self::makeEntity( self::makeEntityId( 42 ) ), 4711 );

		$this->assertSame( $placeholders, $view->getPlaceholders() );
	}

	private function newItemView( $placeholders = [], bool $vueStatementsView = false ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		$termsView = $this->createMock( CacheableEntityTermsView::class );
		$termsView->method( 'getPlaceholders' )->willReturn( $placeholders );

		return new ItemView(
			$templateFactory,
			$termsView,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$this->createMock( StatementSectionsView::class ),
			'en',
			$this->createMock( SiteLinksView::class ),
			[],
			$this->createMock( LocalizedTextProvider::class ),
			$vueStatementsView
		);
	}

}
