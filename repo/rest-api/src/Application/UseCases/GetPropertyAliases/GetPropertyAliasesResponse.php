<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases;

use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesResponse {

	private Aliases $aliases;

	public function __construct( Aliases $aliases ) {
		$this->aliases = $aliases;
	}

	public function getAliases(): Aliases {
		return $this->aliases;
	}

}
