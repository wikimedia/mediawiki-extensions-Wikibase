<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Type\Definition\ResolveInfo;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Term\TermTypes;

/**
 * @license GPL-2.0-or-later
 */
class LabelsResolver {

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	public function fetchLabels( array $rootValue, ResolveInfo $info ): array {
		$entityId = ( new BasicEntityIdParser() )->parse( $rootValue['id'] );
		$languageCodes = array_keys( $info->getFieldSelection() );

		$this->termLookup->prefetchTerms(
			[ $entityId ],
			[ TermTypes::TYPE_LABEL ],
			$languageCodes
		);

		return $this->termLookup->getLabels( $entityId, $languageCodes );
	}
}
