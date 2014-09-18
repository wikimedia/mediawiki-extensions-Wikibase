<?php

namespace Wikibase\DataModel\Entity\Diff;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Christoph Fischer < christoph.fischer@wikimedia.de >
 */
class EntityPatcher {

	/**
	 * @var EntityPatcherStrategy[]
	 */
	private $patcherStrategies;

	public function __construct() {
		$this->registerEntityPatcherStrategy( new ItemPatcher() );
		$this->registerEntityPatcherStrategy( new PropertyPatcher() );
	}

	public function registerEntityPatcherStrategy( EntityPatcherStrategy $patcherStrategy ) {
		$this->patcherStrategies[] = $patcherStrategy;
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @return EntityDocument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
    public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
        return $this->getPatcherStrategy( $entity->getType() )->patchEntity( $entity, $patch );
    }

	private function getPatcherStrategy( $entityType ) {
		foreach ( $this->patcherStrategies as $patcherStrategy ) {
			if ( $patcherStrategy->canPatchEntityType( $entityType ) ) {
				return $patcherStrategy;
			}
		}

		throw new RuntimeException( 'Patching the provided types of entities is not supported' );
	}

}