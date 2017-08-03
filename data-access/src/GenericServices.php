<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * A container/factory of services which don't rely/require repository-specific configuration.
 *
 * @license GPL-2.0+
 */
class GenericServices {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	public function __construct( EntityNamespaceLookup $entityNamespaceLookup ) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		return $this->entityNamespaceLookup;
	}

}
