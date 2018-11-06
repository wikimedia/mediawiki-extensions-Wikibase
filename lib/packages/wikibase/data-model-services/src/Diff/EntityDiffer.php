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
 */
class EntityDiffer {

	/**
	 * @var EntityDifferStrategy[]
	 */
	private $differStrategies;

	public function __construct() {
		$this->registerEntityDifferStrategy( new ItemDiffer() );
		$this->registerEntityDifferStrategy( new PropertyDiffer() );
	}

	public function registerEntityDifferStrategy( EntityDifferStrategy $differStrategy ) {
		$this->differStrategies[] = $differStrategy;
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		$this->assertTypesMatch( $from, $to );

		return $this->getDiffStrategy( $from->getType() )->diffEntities( $from, $to );
	}

	private function assertTypesMatch( EntityDocument $from, EntityDocument $to ) {
		if ( $from->getType() !== $to->getType() ) {
			throw new InvalidArgumentException( 'Can only diff two entities of the same type' );
		}
	}

	/**
	 * @param string $entityType
	 *
	 * @throws RuntimeException
	 * @return EntityDifferStrategy
	 */
	private function getDiffStrategy( $entityType ) {
		foreach ( $this->differStrategies as $diffStrategy ) {
			if ( $diffStrategy->canDiffEntityType( $entityType ) ) {
				return $diffStrategy;
			}
		}

		throw new RuntimeException( 'Diffing the provided types of entities is not supported' );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		return $this->getDiffStrategy( $entity->getType() )->getConstructionDiff( $entity );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {
		return $this->getDiffStrategy( $entity->getType() )->getDestructionDiff( $entity );
	}

}
