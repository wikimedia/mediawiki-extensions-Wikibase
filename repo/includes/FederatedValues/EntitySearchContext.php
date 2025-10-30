<?php
namespace Wikibase\Repo\FederatedValues;

/**
 * Search context constants specific to the Federated Values feature.
 *
 * @license GPL-2.0-or-later
 */
final class EntitySearchContext {
	public const VALUE = 'value';

	public static function toArray(): array {
		// Expose constant names => values for JSON export
		return [
			'VALUE' => self::VALUE,
		];
	}
}
