<?php

namespace Wikibase\DataModel\Entity\Diff;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
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

		foreach ( $this->differStrategies as $diffStrategy ) {
			if ( $diffStrategy->canDiffEntityType( $from->getType() ) ) {
				return $diffStrategy->diffEntities( $from, $to );
			}
		}

		throw new RuntimeException( 'Diffing the provided types of entities is not supported' );
	}

	private function assertTypesMatch( EntityDocument $from, EntityDocument $to ) {
		if ( $from->getType() !== $to->getType() ) {
			throw new InvalidArgumentException( 'Can only diff two entities of the same type' );
		}
	}

}