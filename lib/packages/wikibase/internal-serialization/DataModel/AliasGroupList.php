<?php

namespace Wikibase\DataModel\Term;

class AliasGroupList {

	private $aliases;

	/**
	 * @param AliasGroup[] $aliases
	 */
	public function __construct( array $aliases ) {
		$this->aliases = $aliases;
	}

	/**
	 * @return AliasGroup[]
	 */
	public function getAliasGroups() {
		return $this->aliases;
	}

}