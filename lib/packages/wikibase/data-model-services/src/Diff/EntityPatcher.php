<?php

namespace Wikibase\DataModel\Services\Diff;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
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
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		$this->getPatcherStrategy( $entity->getType() )->patchEntity( $entity, $patch );
	}

	/**
	 * @param string $entityType
	 *
	 * @throws RuntimeException
	 * @return EntityPatcherStrategy
	 */
	private function getPatcherStrategy( $entityType ) {
		foreach ( $this->patcherStrategies as $patcherStrategy ) {
			if ( $patcherStrategy->canPatchEntityType( $entityType ) ) {
				return $patcherStrategy;
			}
		}

		throw new RuntimeException( 'Patching the provided types of entities is not supported' );
	}

}
