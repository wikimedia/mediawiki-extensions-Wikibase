<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 */
class Value {

	/**
	 * @param DataValue|null $content Guaranteed to be non-null if value type is "value", always null otherwise.
	 */
	public function __construct( public readonly ?DataValue $content = null ) {
	}

}
