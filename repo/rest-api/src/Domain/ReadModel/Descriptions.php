<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class Descriptions extends ArrayObject {

	public function __construct( Description ...$descriptions ) {
		parent::__construct(
			array_combine(
				array_map( fn( Description $desc ) => $desc->getLanguageCode(), $descriptions ),
				$descriptions
			)
		);
	}

	public static function fromTermList( TermList $list ): self {
		$descriptions = [];
		foreach ( $list->getIterator() as $term ) {
			$descriptions[] = Description::fromTerm( $term );
		}
		return new self( ...$descriptions );
	}

}
