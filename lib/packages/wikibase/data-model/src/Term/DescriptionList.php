<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DescriptionList extends TermList {

	/**
	 * @param Description[] $descriptions
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $descriptions ) {
		foreach ( $descriptions as $description ) {
			if ( !( $description instanceof Description ) ) {
				throw new InvalidArgumentException( 'DescriptionList can only contain Description instances' );
			}
		}

		parent::__construct( $descriptions );
	}

}