<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class WbSearchEntitiesControllerDispatcher {

	/**
	 * @param callable[] $callbacks entity type string => callable returning WbSearchEntitiesController
	 */
	public function __construct( private readonly array $callbacks ) {
	}

	public function getControllerForEntityType( string $entityType ): WbSearchEntitiesController {
		if ( !isset( $this->callbacks[$entityType] ) ) {
			throw new InvalidArgumentException( "No controller registered for entity type '$entityType'" );
		}

		return ( $this->callbacks[$entityType] )();
	}

}
