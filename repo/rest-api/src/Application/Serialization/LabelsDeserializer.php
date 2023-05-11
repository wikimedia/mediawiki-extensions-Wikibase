<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class LabelsDeserializer {

	public function deserialize( array $serialization ): TermList {
		$terms = [];

		foreach ( $serialization as $language => $text ) {
			if ( $text === '' ) {
				throw new EmptyLabelException( $language, '' );
			}
			$terms[] = new Term( $language, $text );
		}

		return new TermList( $terms );
	}

}
