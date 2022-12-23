<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class LabelsSerializer {

	public function serialize( TermList $labels ): ArrayObject {
		return new ArrayObject( $labels->toTextArray() );
	}

}
