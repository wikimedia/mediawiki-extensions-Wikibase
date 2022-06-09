<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class NullEntitySearchHelper implements EntitySearchHelper {

	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	) {
		return [];
	}

}
