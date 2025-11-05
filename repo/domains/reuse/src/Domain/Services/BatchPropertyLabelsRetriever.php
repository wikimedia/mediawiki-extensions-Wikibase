<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
interface BatchPropertyLabelsRetriever {

	/**
	 * @param PropertyId[] $propertyIds
	 * @param string[] $languageCodes
	 *
	 * @return PropertyLabelsBatch
	 */
	public function getPropertyLabels( array $propertyIds, array $languageCodes ): PropertyLabelsBatch;

}
