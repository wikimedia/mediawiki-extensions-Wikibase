<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePair {

	// will add the value in upcoming patches
	public function __construct( public readonly PredicateProperty $property ) {
	}

}
