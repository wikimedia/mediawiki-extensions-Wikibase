<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceLookup {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var array
	 */
	private $subEntityTypesMap;

	public function __construct( EntitySourceDefinitions $entitySourceDefinitions, array $subEntityTypesMap ) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->subEntityTypesMap = $subEntityTypesMap;
	}

	public function getEntitySourceById( EntityId $id ): EntitySource {
		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			if (
				strpos( $id->getSerialization(), $source->getConceptBaseUri() ) === 0 &&
				$source->getType() === EntitySource::TYPE_API
			) {
				return $source;
			}
		}

		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			$entityType = $this->getTopLevelEntityType( $id->getEntityType() );

			// TODO this returns the first entity source that is not an api source that has this entity type. In the case there is more than
			// one configured, this could be bad.
			if ( $source->getType() === EntitySource::TYPE_DB && in_array( $entityType, $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		throw new LogicException( 'Could not find a matching entity source for id: "' . $id->getSerialization() . '"' );
	}

	private function getTopLevelEntityType( string $type ): string {
		foreach ( $this->subEntityTypesMap as $topLevelType => $subEntityTypes ) {
			if ( in_array( $type, $subEntityTypes ) ) {
				return $topLevelType;
			}
		}

		return $type;
	}

}
