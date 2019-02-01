<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * @license GPL-2.0-or-later
 */
class FakeEntityInfoBuilder implements EntityInfoBuilder {

	private $entityInfo;

	public function __construct( array $entityInfo ) {
		$this->entityInfo = $entityInfo;
	}
	
	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		// TODO: only return id matches
		// TODO: add element for each $languageCodes
		return new EntityInfo( $this->entityInfo );
	}

}