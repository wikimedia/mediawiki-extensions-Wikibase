<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidDescriptionException;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsDeserializer {

	/**
	 * @throws InvalidDescriptionException
	 * @throws EmptyDescriptionException
	 */
	public function deserialize( array $serialization ): TermList {
		$terms = [];
		foreach ( $serialization as $language => $text ) {
			// casting to string required - while json keys are always strings, php converts numeric keys to integers
			$language = (string)$language;

			if ( !is_string( $text ) ) {
				throw new InvalidDescriptionException( $language, $text );
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
