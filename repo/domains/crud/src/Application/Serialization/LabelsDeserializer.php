<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidLabelException;

/**
 * @license GPL-2.0-or-later
 */
class LabelsDeserializer {

	/**
	 * @throws InvalidLabelException
	 * @throws EmptyLabelException
	 */
	public function deserialize( array $serialization ): TermList {
		$terms = [];
		foreach ( $serialization as $language => $text ) {
			// casting to string required - while json keys are always strings, php converts numeric keys to integers
			$language = (string)$language;

			if ( !is_string( $text ) ) {
				throw new InvalidLabelException( $language, $text );
			}

			$trimmedText = trim( $text );
			if ( $trimmedText === '' ) {
				throw new EmptyLabelException( $language, '' );
			}

			$terms[] = new Term( $language, $trimmedText );
		}

		return new TermList( $terms );
	}

}
