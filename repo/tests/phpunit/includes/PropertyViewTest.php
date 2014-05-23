<?php

namespace Wikibase\Test;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Entity;
use Wikibase\Property;

/**
 * @covers Wikibase\PropertyView
 *
 * @group Wikibase
 * @group WikibasePropertyView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Database
 * @group medium
 */
class PropertyViewTest extends EntityViewTest {

	protected function getEntityViewClass() {
		return 'Wikibase\PropertyView';
	}

	/**
	 * @param EntityId $id
	 * @param Claim[] $claims
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id, $claims = array() ) {
		return $this->makeProperty( $id, 'string', $claims );
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new PropertyId( "P$n" );
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

	public function testGetHtmlForToc() {
		$entityView = $this->newEntityView( Property::ENTITY_TYPE );
		$toc = $entityView->getHtmlForToc();

		$this->assertSame( '', $toc, "properties should currently not have a TOC" );
	}
}
