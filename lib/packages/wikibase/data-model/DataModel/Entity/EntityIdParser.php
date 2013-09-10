<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for objects that can parse EntityIds
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface EntityIdParser {

	/**
	 * @since 0.5
	 *
	 * @param string $entityId
	 *
	 * @return mixed
	 */
	public function parse( $entityId );

}