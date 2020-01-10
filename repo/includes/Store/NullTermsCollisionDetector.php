<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * TermsCollisionDetector implementation that does nothing (always returns no collision)
 *
 * @license GPL-2.0-or-later
 */
class NullTermsCollisionDetector implements TermsCollisionDetector {

	public function detectLabelCollision(
		string $lang,
		string $label
	): ?EntityId {
		return null;
	}

	public function detectLabelAndDescriptionCollision(
		string $lang,
		string $label,
		string $description
	): ?EntityId {
		return null;
	}
}
