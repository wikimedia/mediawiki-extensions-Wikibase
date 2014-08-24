<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\ItemView
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Database
 * @group medium
 */
class ItemViewTest extends EntityViewTest {

	protected function getEntityViewClass() {
		return 'Wikibase\ItemView';
	}

	/**
	 * @param EntityId $id
	 * @param Claim[] $claims
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id, $claims = array() ) {
		return $this->makeItem( $id, $claims );
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new ItemId( "Q$n");
	}

}
