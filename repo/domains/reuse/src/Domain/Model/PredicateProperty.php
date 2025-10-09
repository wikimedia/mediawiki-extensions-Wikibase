<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PredicateProperty {

	/**
	 * @param PropertyId $id
	 * @param string|null $dataType Existing Properties always have a data type.
	 * null indicates that the Property was deleted.
	 */
	public function __construct( public readonly PropertyId $id, public readonly ?string $dataType ) {
	}

}
