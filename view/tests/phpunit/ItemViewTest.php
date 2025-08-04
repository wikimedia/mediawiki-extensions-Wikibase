<?php

namespace Wikibase\View\Tests;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Grouper\FilteringStatementGrouper;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\ItemView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SiteLinksView;
use Wikibase\View\StatementGroupListView;
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
				'item' => self::newEntityForStatements( [
					new Statement( new PropertyValueSnak(
						new NumericPropertyId( 'P1' ),
						new StringValue( 'p1' )
					), new SnakList( [
						new PropertyValueSnak(
							new NumericPropertyId( 'P10' ),
							new StringValue( 'qualifier10' )
						),
					] ) ),
					new Statement( new PropertyValueSnak(
						new NumericPropertyId( 'P2' ),
						new StringValue( 'p2' )
					) ),
				] ),
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
			$this->assertStringContainsString( 'wikibase-wbui2025-statementgrouplistview', $html );
			$this->assertStringContainsString( '<div>a snak', $html );
		} else {
			$this->assertStringNotContainsString( 'wikibase-wbui2025-statementgrouplistview', $html );
		}
	}

	/** @dataProvider provideTestVueStatementsView */
	public function testVuePropertyName( callable $viewFactory, Item $item, bool $vueStatementsExpected ) {
		$view = $viewFactory( $this );
		$output = $view->getContent( $item, null );
		$html = $output->getHtml();
		if ( $vueStatementsExpected ) {
			$this->assertStringContainsString( 'wikibase-wbui2025-property-name-link', $html );
			$this->assertStringContainsString( 'data-property-id="P2"', $html );
			$this->assertStringContainsString( '<a title="Property:P2"', $html );
			$this->assertStringContainsString( 'href="/wiki/Property:P2"', $html );
		} else {
			$this->assertStringNotContainsString( 'wikibase-wbui2025-property-name-link', $html );
		}
	}

	/** @dataProvider provideTestVueStatementsView */
	public function testVueQualifiers( callable $viewFactory, Item $item, bool $vueStatementsExpected ): void {
		$view = $viewFactory( $this );
		$output = $view->getContent( $item, null );
		$html = $output->getHtml();
		if ( $vueStatementsExpected ) {
			$this->assertStringContainsString( 'wikibase-wbui2025-qualifiers', $html );
			$this->assertStringContainsString( 'data-property-id="P10"', $html );
			$this->assertStringContainsString( '<a title="Property:P10"', $html );
			$this->assertStringContainsString( 'href="/wiki/Property:P10"', $html );
			$this->assertStringContainsString( '<div>a snak', $html );
		} else {
			$this->assertStringNotContainsString( 'wikibase-wbui2025-qualifier', $html );
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
		$propertyDataTypeLookup = $this->createConfiguredMock( PropertyDataTypeLookup::class, [
			'getDataTypeIdForProperty' => 'string',
		] );
		$snakFormatter = $this->createConfiguredMock( SnakFormatter::class, [
			'formatSnak' => '<div>a snak :)</div>',
		] );
		$textProvider = $this->createMock( LocalizedTextProvider::class );
		$statementSectionsView = new StatementSectionsView(
			$templateFactory,
			new FilteringStatementGrouper( [ 'statement' => null ] ),
			$this->createMock( StatementGroupListView::class ),
			$textProvider,
			$snakFormatter,
			new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT ),
			$propertyDataTypeLookup,
			$this->getEntityIdFormatterFactory(),
			$this->getEntityIdParser(),
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			$vueStatementsView
		);

		return new ItemView(
			$templateFactory,
			$termsView,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$statementSectionsView,
			'en',
			$this->createMock( SiteLinksView::class ),
			[],
			$textProvider,
		);
	}

	/**
	 * @return EntityIdFormatterFactory
	 */
	private function getEntityIdFormatterFactory() {
		$entityIdFormatter = $this->createMock( EntityIdFormatter::class );

		$entityIdFormatter->method( 'formatEntityId' )
		->willReturnCallback( function ( EntityId $entityId ) {
			$propertyId = $entityId->getSerialization();
			return "<a title=\"Property:$propertyId\" href=\"/wiki/Property:$propertyId\">Property $propertyId</a>'";
		} );

		$formatterFactory = $this->createMock( EntityIdFormatterFactory::class );

		$formatterFactory->method( 'getEntityIdFormatter' )
			->willReturn( $entityIdFormatter );

		return $formatterFactory;
	}

	/*
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		$mock = $this->createMock( EntityIdParser::class );
		$mock->method( 'parse' )
			->willReturnCallback( function( $itemString ) {
				return new NumericPropertyId( $itemString );
			} );
		return $mock;
	}

}
