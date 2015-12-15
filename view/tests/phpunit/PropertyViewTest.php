<?php

namespace Wikibase\View\Tests;

use DataTypes\DataTypeFactory;
use Language;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\PropertyView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\EntityView
 * @covers Wikibase\View\PropertyView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 * @uses Wikibase\View\TextInjector
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyViewTest extends EntityViewTest {

	/**
	 * @param EntityId $id
	 *
	 * @return Property
	 */
	protected function makeEntity( EntityId $id ) {
		$property = Property::newFromType( 'string' );
		$property->setId( $id );

		$property->setLabel( 'en', "label:$id" );
		$property->setDescription( 'en', "description:$id" );

		return $property;
	}

	/**
	 * @return PropertyId
	 */
	protected function getEntityId() {
		return new PropertyId( "P1" );
	}

	/**
	 * Prepares the given entity data for comparison with $entity.
	 * That is, this method should add any extra data from $entity to $entityData.
	 *
	 * @param Entity $entity
	 * @param array $entityData
	 */
	protected function prepareEntityData( Entity $entity, array &$entityData ) {
		/* @var Property $entity */
		$entityData['datatype'] = $entity->getDataTypeId();
	}

	/**
	 * @return DataTypeFactory
	 */
	private function getDataTypeFactory() {
		return new DataTypeFactory( array( 'string' => 'string' ) );
	}

	/**
	 * @return PropertyView
	 */
	protected function newEntityView() {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$propertyView = new PropertyView(
			$templateFactory,
			$this->getMockBuilder( 'Wikibase\View\EntityTermsView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( 'Wikibase\View\StatementSectionsView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getDataTypeFactory(),
			Language::factory( 'qqx' )
		);
		return $propertyView;
	}

	public function provideTestGetHtml() {
		$id = $this->getEntityId();
		$property = $this->makeEntity( $id );

		// FIXME: add statements
		$statements = array();
		$property->setStatements( new StatementList( $statements ) );

		$cases = parent::provideTestGetHtml();
		$cases[] = array(
			$this->newEntityRevision( $property ),
			array(
				'CSS class' => '!class="wikibase-entityview wb-property"!',
				'data type heading' => '!class="wb-section-heading section-heading wikibase-propertypage-datatype".*\(wikibase-propertypage-datatype\)!',
				'data type' => '!class="wikibase-propertyview-datatype-value".*\(datatypes-type-string\)!',
				// FIXME: make sure statements are shown
				// FIXME: make sure the termbox is shown
				'footer' => '!\(wikibase-property-footer\)!',
			)
		);

		$propertyWithBadType = Property::newFromType( 'YaddaYadda' );
		$propertyWithBadType->setId( $id );

		$cases[] = array(
			$this->newEntityRevision( $propertyWithBadType ),
			array(
				'bad data type error' => '!\(wikibase-propertypage-bad-datatype: YaddaYadda\)!',
			)
		);

		return $cases;
	}

}
