<?php

declare( strict_types=1 );
namespace Wikibase\Lib\TermFallbackCache;

use Wikibase\DataModel\Term\TermFallback;

/**
 * @license GPL-2.0-or-later
 */
trait TermFallbackSerializerTrait {

	private function serialize( ?TermFallback $termFallback ): ?array {
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

	private function unserialize( ?array $serialized ): ?TermFallback {
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
