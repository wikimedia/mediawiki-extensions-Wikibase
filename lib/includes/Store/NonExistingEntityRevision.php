<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityRevision;

/**
 * Representing an EntityRevision that does not exist.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
class NonExistingEntityRevision extends EntityRevision {

	public function __construct( EntityDocument $entity ) {
		parent::__construct( $entity );
	}

}
