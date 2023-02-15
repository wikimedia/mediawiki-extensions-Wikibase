<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class Labels extends ArrayObject {

	public function __construct( Label ...$labels ) {
		parent::__construct(
			array_combine(
				array_map( fn( Label $l ) => $l->getLanguageCode(), $labels ),
				$labels
			)
		);
	}

	public static function fromTermList( TermList $list ): self {
		$labels = [];
		foreach ( $list->getIterator() as $term ) {
			$labels[] = Label::fromTerm( $term );
		}
		return new self( ...$labels );
	}

}
