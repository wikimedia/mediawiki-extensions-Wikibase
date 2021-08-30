<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceLookup {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var SubEntityTypesMapper
	 */
	private $subEntityTypesMapper;

	public function __construct( EntitySourceDefinitions $entitySourceDefinitions, SubEntityTypesMapper $subEntityTypesMapper ) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->subEntityTypesMapper = $subEntityTypesMapper;
	}

	public function getEntitySourceById( EntityId $id ): EntitySource {
		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			if (
				strpos( $id->getSerialization(), $source->getConceptBaseUri() ) === 0 &&
				$source->getType() === ApiEntitySource::TYPE
			) {
				return $source;
			}
		}

		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			$idType = $id->getEntityType();
			$topLevelEntityType = $this->subEntityTypesMapper->getParentEntityType( $idType ) ?? $idType;

			// TODO this returns the first entity source that is not an api source that has this entity type. In the case there is more than
			// one configured, this could be bad.
			if ( $source->getType() === DatabaseEntitySource::TYPE && in_array( $topLevelEntityType, $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		throw new LogicException( 'Could not find a matching entity source for id: "' . $id->getSerialization() . '"' );
	}

}
