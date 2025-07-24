<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Type\Definition\ResolveInfo;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;

/**
 * @license GPL-2.0-or-later
 */
class LabelsResolver {

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	public function fetchLabels( array $rootValue, ResolveInfo $info ): array {
		$itemId = new ItemId( $rootValue['id'] );
		$languageCodes = array_keys( $info->getFieldSelection() );

		$this->termLookup->prefetchTerms(
			[ $itemId ],
			[ TermTypes::TYPE_LABEL ],
			$languageCodes
		);

		return $this->termLookup->getLabels( $itemId, $languageCodes );
	}
}
