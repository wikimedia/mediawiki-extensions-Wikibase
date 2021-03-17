<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\EntityTypeDefinitions;

/**
 * A container/factory of services which don't rely/require repository-specific configuration.
 *
 * @license GPL-2.0-or-later
 */
class GenericServices {

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct(
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

}
