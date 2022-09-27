<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Trait for code reuse of mapping for entity term storage
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
trait NormalizedTermStorageMappingTrait {

	/** @var NormalizedTermStorageMapping|null */
	private $mapping;

	abstract protected function makeMapping(): NormalizedTermStorageMapping;

	private function getMapping(): NormalizedTermStorageMapping {
		if ( $this->mapping === null ) {
			$this->mapping = $this->makeMapping();
		}
		return $this->mapping;
	}
}
