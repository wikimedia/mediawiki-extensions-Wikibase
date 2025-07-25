<?php

namespace Wikibase\View\Tests;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\PropertyView;
use Wikibase\View\SnakHtmlGenerator;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\View\EntityView
 * @covers \Wikibase\View\PropertyView
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
class PropertyViewTest extends EntityViewTestCase {

	/**
	 * @param EntityId|NumericPropertyId $id
	 * @param Statement[] $statements
	 *
	 * @return Property
	 */
	protected static function makeEntity( EntityId $id, array $statements = [] ) {
		$property = Property::newFromType( 'string' );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		$property->setStatements( new StatementList( ...$statements ) );

		return $property;
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return NumericPropertyId
	 */
	protected static function makeEntityId( $n ) {
		return new NumericPropertyId( "P$n" );
	}

	/**
	 * Prepares the given entity data for comparison with $entity.
	 * That is, this method should add any extra data from $entity to $entityData.
	 *
	 * @param EntityDocument $entity
	 * @param array &$entityData
	 */
	protected function prepareEntityData( EntityDocument $entity, array &$entityData ) {
		/** @var Property $entity */
		$entityData['datatype'] = $entity->getDataTypeId();
	}

	public static function provideTestGetHtml() {
		return [
			[
				fn ( self $self ) => $self->newPropertyView(),
				self::newEntityForStatements( [] ),
				'/wb-property/',
			],
		];
	}

	public function testTermsViewPlaceholdersArePropagated() {
		$placeholders = [ 'a' => 'b' ];
		$itemView = $this->newPropertyView( $placeholders );

		$view = $itemView->getContent( self::makeEntity( self::makeEntityId( 42 ) ), 4711 );

		$this->assertSame( $placeholders, $view->getPlaceholders() );
	}

	private function newPropertyView( $placeholders = [], bool $vueStatementsView = false ) {
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
			$this->createConfiguredMock( StatementGrouper::class, [ 'groupStatements' => [] ] ),
			$this->createMock( StatementGroupListView::class ),
			$textProvider,
			$this->createMock( SnakHtmlGenerator::class ),
			$snakFormatter,
			new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT ),
			$propertyDataTypeLookup,
			'en',
			$vueStatementsView
		);

		return new PropertyView(
			$templateFactory,
			$termsView,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$statementSectionsView,
			$this->getDataTypeFactory(),
			'en',
			$this->createMock( LocalizedTextProvider::class )
		);
	}

	private function getDataTypeFactory() {
		return new DataTypeFactory( [ 'type' => 'datavalue', 'string' => 'string' ] );
	}

	public static function provideTestVueStatementsView(): iterable {
		return [
			[
				'viewFactory' => fn ( self $self ) => $self->newPropertyView(),
				'property' => self::newEntityForStatements( [] ),
				'vueStatementsExpected' => false,
			],
			[
				'viewFactory' => fn ( self $self ) => $self->newPropertyView( [], true ),
				'property' => self::newEntityForStatements( [
					new Statement( new PropertyValueSnak(
						new NumericPropertyId( 'P1' ),
						new StringValue( 'p1' )
					) ),
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
	public function testVueStatementsView( callable $viewFactory, Property $property, bool $vueStatementsExpected ) {
		$view = $viewFactory( $this );
		$output = $view->getContent( $property, null );
		$html = $output->getHtml();

		if ( $vueStatementsExpected ) {
			$this->assertStringContainsString( 'wikibase-wbui2025-statementgrouplistview', $html );
			$this->assertStringContainsString( '<div>a snak', $html );
		} else {
			$this->assertStringNotContainsString( 'wikibase-wbui2025-statementgrouplistview', $html );
		}
	}

}
