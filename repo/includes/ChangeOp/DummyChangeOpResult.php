<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class DummyChangeOpResult has no result
 */
class DummyChangeOpResult extends GenericChangeOpResult {

	public function __construct( EntityId $entityId = null ) {
		 parent::__construct( $entityId, false );
	}

}
