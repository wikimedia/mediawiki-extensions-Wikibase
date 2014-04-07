<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelList extends TermList {

	/**
	 * @param Label[] $labels
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $labels ) {
		foreach ( $labels as $label ) {
			if ( !( $label instanceof Label ) ) {
				throw new InvalidArgumentException( 'LabelList can only contain Label instances' );
			}
		}

		parent::__construct( $labels );
	}

}