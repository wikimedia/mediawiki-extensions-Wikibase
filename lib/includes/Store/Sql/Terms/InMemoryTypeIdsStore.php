<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Acquires and resolves unique and constant ids of types, stored in memory.
 *
 * @license GPL-2.0-or-later
 */
class InMemoryTypeIdsStore implements TypeIdsAcquirer, TypeIdsResolver, TypeIdsLookup {

	/** @var int[] */
	private $types = [];
	/** @var int */
	private $lastId = 0;

	public function acquireTypeIds( array $types ): array {
		$ids = [];
		foreach ( $types as $type ) {
			if ( !isset( $this->types[$type] ) ) {
				$this->types[$type] = ++$this->lastId;
			}
			$ids[$type] = $this->types[$type];
		}

		return $ids;
	}

	public function resolveTypeIds( array $typeIds ): array {
		$types = [];
		foreach ( $this->types as $typeName => $typeId ) {
			if ( in_array( $typeId, $typeIds ) ) {
				$types[$typeId] = $typeName;
			}
		}
		return $types;
	}

	public function lookupTypeIds( array $types ): array {
		$ids = [];
		foreach ( $types as $type ) {
			$ids[$type] = $this->types[$type] ?? null;
		}

		return $ids;
	}

}
