<?php

declare( strict_types=1 );
namespace Wikibase\Lib\FormatterCache;

use Wikibase\DataModel\Term\TermFallback;

/**
 * @license GPL-2.0-or-later
 */
trait TermFallbackSerializerTrait {

	/**
	 * @param TermFallback|null $termFallback
	 * @return array|null
	 */
	private function serialize( ?TermFallback $termFallback ) {
		if ( $termFallback === null ) {
			return null;
		}

		return [
			'language' => $termFallback->getActualLanguageCode(),
			'value' => $termFallback->getText(),
			'requestLanguage' => $termFallback->getLanguageCode(),
			'sourceLanguage' => $termFallback->getSourceLanguageCode(),
		];
	}

	/**
	 * @param array|null $serialized
	 * @return null|TermFallback
	 */
	private function unserialize( $serialized ): ?TermFallback {
		if ( $serialized === null ) {
			return null;
		}

		$termData = $serialized;
		return new TermFallback(
			$termData['requestLanguage'],
			$termData['value'],
			$termData['language'],
			$termData['sourceLanguage']
		);
	}
}
