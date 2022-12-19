<?php

namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\PropertyView;
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
	protected function makeEntity( EntityId $id, array $statements = [] ) {
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
	protected function makeEntityId( $n ) {
		return new NumericPropertyId( "P$n" );
	}

	/**
	 * Prepares the given entity data for comparison with $entity.
	 * That is, this method should add any extra data from $entity to $entityData.
	 *
	 * @param EntityDocument $entity
	 * @param array $entityData
	 */
	protected function prepareEntityData( EntityDocument $entity, array &$entityData ) {
		/** @var Property $entity */
		$entityData['datatype'] = $entity->getDataTypeId();
	}

	public function provideTestGetHtml() {
		$propertyView = $this->newPropertyView();

		return [
			[
				$propertyView,
				$this->newEntityForStatements( [] ),
				'/wb-property/',
			],
		];
	}

	public function testTermsViewPlaceholdersArePropagated() {
		$placeholders = [ 'a' => 'b' ];
		$itemView = $this->newPropertyView( $placeholders );

		$view = $itemView->getContent( $this->makeEntity( $this->makeEntityId( 42 ) ), 4711 );

		$this->assertSame( $placeholders, $view->getPlaceholders() );
	}

	private function newPropertyView( $placeholders = [] ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		$termsView = $this->createMock( CacheableEntityTermsView::class );
		$termsView->method( 'getPlaceholders' )->willReturn( $placeholders );

		return new PropertyView(
			$templateFactory,
			$termsView,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$this->createMock( StatementSectionsView::class ),
			$this->getDataTypeFactory(),
			'en',
			$this->createMock( LocalizedTextProvider::class )
		);
	}

	private function getDataTypeFactory() {
		return new DataTypeFactory( [ 'type' => 'datavalue', 'string' => 'string' ] );
	}

}
