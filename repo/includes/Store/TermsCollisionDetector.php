<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermList;

/**
 * Find collisions of term values with existing terms in store
 *
 * @license GPL-2.0-or-later
 */
interface TermsCollisionDetector {

	/**
	 * Returns an entity id that collides with given label in given languages, if any
	 * @param  string        $lang
	 * @param  string        $label
	 * @return EntityId|null
	 */
	public function detectLabelCollision(
		string $lang,
		string $label
	): ?EntityId;

	/**
	 * Returns an entity id that collides with given label and description in given languages, if any
	 * @param  string        $lang
	 * @param  string        $label
	 * @param  string        $description
	 * @return EntityId|null
	 */
	public function detectLabelAndDescriptionCollision(
		string $lang,
		string $label,
		string $description
	): ?EntityId;

	/**
	 * Returns entity ids that collides with given labels in given languages
	 * @param  TermList        $labels
	 *
	 * // example return array
	 * [
	 * 	'P1' => [
	 * 		Term('en', 'label'),
	 * 		Term('de', 'label')
	 * 	]
	 * ]
	 *
	 * @return array
	 */
	public function detectLabelsCollision(
		TermList $labels
	): array;
}
