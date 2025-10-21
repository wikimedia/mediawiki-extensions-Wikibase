<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 */
class Value {

	public function __construct( public readonly DataValue $content ) {
	}

}
