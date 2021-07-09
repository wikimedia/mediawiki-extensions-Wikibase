<?php
declare( strict_types=1 );

namespace Wikibase\Lib;

/**
 * Thin wrapper around EntityTypeDefinitions::SUB_ENTITY_TYPES
 *
 * @license GPL-2.0-or-later
 */
class SubEntityTypesMapper {

	/**
	 * @var string[][] map of top level entity types to their sub entity types
	 */
	private $typeMap;

	public function __construct( array $typeMap ) {
		$this->typeMap = $typeMap;
	}

	/**
	 * Returns the parent entity type for a sub entity type, or null if given an unknown type or not a sub entity type.
	 */
	public function getParentEntityType( string $type ): ?string {
		foreach ( $this->typeMap as $topLevelType => $subEntityTypes ) {
			if ( in_array( $type, $subEntityTypes ) ) {
				return $topLevelType;
			}
		}

		return null;
	}

	public function getSubEntityTypes( string $type ): array {
		return $this->typeMap[$type] ?? [];
	}

}
