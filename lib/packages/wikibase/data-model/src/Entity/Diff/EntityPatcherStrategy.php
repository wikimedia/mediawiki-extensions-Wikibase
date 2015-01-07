<?php

namespace Wikibase\DataModel\Entity\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityPatcherStrategy {

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType );

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch );

}
