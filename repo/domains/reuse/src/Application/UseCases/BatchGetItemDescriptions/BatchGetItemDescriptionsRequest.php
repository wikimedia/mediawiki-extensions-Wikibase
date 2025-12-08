<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemDescriptionsRequest {

	/**
	 * @param string[] $itemIds
	 * @param string[] $languageCodes
	 */
	public function __construct( public readonly array $itemIds, public readonly array $languageCodes ) {
	}

}
