<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetPropertyLabelsRequest {

	/**
	 * @param string[] $propertyIds
	 * @param string[] $languageCodes
	 */
	public function __construct( public readonly array $propertyIds, public readonly array $languageCodes ) {
	}
}
