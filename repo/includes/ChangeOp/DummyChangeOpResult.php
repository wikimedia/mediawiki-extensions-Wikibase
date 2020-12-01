<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Only references the entity, always validates successfully
 * and always indicates no changes to the entity.
 *
 * Suitable as a result for a no-op ChangeOp implementation
 * @see  NullChangeOp
 * @license GPL-2.0-or-later
 */
class DummyChangeOpResult extends GenericChangeOpResult {

	public function __construct( EntityId $entityId = null ) {
		 parent::__construct( $entityId, false );
	}

}
