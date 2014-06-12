<?php

namespace Wikibase\DataModel\Entity\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityDifferStrategy {

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canDiffEntityType( $entityType );

	/**
	 * @param Entity $from
	 * @param Entity $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( Entity $from, Entity $to );

}