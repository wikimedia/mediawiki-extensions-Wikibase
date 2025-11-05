<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabelsRequest {

	/**
	 * @param string[] $itemIds
	 * @param string[] $languageCodes
	 */
	public function __construct( public readonly array $itemIds, public readonly array $languageCodes ) {
	}

}
