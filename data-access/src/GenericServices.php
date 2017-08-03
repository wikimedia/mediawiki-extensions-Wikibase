<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\Store\EntityNamespaceLookup;

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
