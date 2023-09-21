<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsDeserializer {

	public function deserialize( array $serialization ): TermList {
		$terms = [];

		foreach ( $serialization as $language => $text ) {
			if ( !is_string( $text ) ) {
				throw new InvalidFieldException( $language, $text );
			}

			$trimmedText = trim( $text );
			if ( $trimmedText === '' ) {
				throw new EmptyDescriptionException( $language, '' );
			}
			$terms[] = new Term( $language, $trimmedText );
		}

		return new TermList( $terms );
	}

}
