<?php

namespace Wikibase\View\Tests;

use DataTypes\DataTypeFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\EntityTermsView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\PropertyView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\EntityView
 * @covers Wikibase\View\PropertyView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyViewTest extends EntityViewTest {

	/**
	 * @param EntityId|PropertyId $id
	 * @param Statement[] $statements
	 *
	 * @return Property
	 */
	protected function makeEntity( EntityId $id, array $statements = array() ) {
		$property = Property::newFromType( 'string' );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		$property->setStatements( new StatementList( $statements ) );

		return $property;
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return PropertyId
	 */
	protected function makeEntityId( $n ) {
		return new PropertyId( "P$n" );
	}

	/**
	 * Prepares the given entity data for comparison with $entity.
	 * That is, this method should add any extra data from $entity to $entityData.
	 *
	 * @param EntityDocument $entity
	 * @param array $entityData
	 */
	protected function prepareEntityData( EntityDocument $entity, array &$entityData ) {
		/* @var Property $entity */
		$entityData['datatype'] = $entity->getDataTypeId();
	}

	public function provideTestGetHtml() {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$propertyView = new PropertyView(
			$templateFactory,
			$this->getMock( EntityTermsView::class ),
			$this->getMock( LanguageDirectionalityLookup::class ),
			$this->getMockBuilder( StatementSectionsView::class )
				->disableOriginalConstructor()
				->getMock(),
			$this->getDataTypeFactory(),
			'en',
			$this->getMock( LocalizedTextProvider::class )
		);

		return array(
			array(
				$propertyView,
				$this->newEntityForStatements( array() ),
				'/wb-property/'
			)
		);
	}

	private function getDataTypeFactory() {
		return new DataTypeFactory( array( 'type' => 'datavalue', 'string' => 'string' ) );
	}

}
