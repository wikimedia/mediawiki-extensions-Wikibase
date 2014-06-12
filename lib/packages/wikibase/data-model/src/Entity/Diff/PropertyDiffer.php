<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\Differ\MapDiffer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Property;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyDiffer implements EntityDifferStrategy {

	/**
	 * @param string $entityType
	 *
	 * @return bool
	 */
	public function canDiffEntityType( $entityType ) {
		return $entityType === 'property';
	}

	/**
	 * @param Entity $from
	 * @param Entity $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( Entity $from, Entity $to ) {
		$this->assertIsProperty( $from );
		$this->assertIsProperty( $to );

		return $this->diffProperties( $from, $to );
	}

	private function assertIsProperty( Entity $item ) {
		if ( !( $item instanceof Property ) ) {
			throw new InvalidArgumentException( 'All entities need to be properties' );
		}
	}

	public function diffProperties( Property $from, Property $to ) {
		$differ = new MapDiffer( true );
		$diffOps = $differ->doDiff( $this->toDiffArray( $from ), $this->toDiffArray( $to ) );

		return new EntityDiff( $diffOps );
	}

	private function toDiffArray( Property $item ) {
		$array = array();

		$array['aliases'] = $item->getAllAliases();
		$array['label'] = $item->getLabels();
		$array['description'] = $item->getDescriptions();

		return $array;
	}

}