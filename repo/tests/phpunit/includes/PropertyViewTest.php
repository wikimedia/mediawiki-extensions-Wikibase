<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement;

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
	 * @param Statement[] $statements
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id, array $statements = array() ) {
		return $this->makeProperty( $id, 'string', $statements );
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

}
