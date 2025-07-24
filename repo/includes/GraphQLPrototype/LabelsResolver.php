<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Deferred;
use GraphQL\Type\Definition\ResolveInfo;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Term\TermTypes;

/**
 * @license GPL-2.0-or-later
 */
class LabelsResolver {

	private array $entityIdsBatch = [];
	private array $languageCodesBatch = [];
	private bool $hasPrefetched = false;

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	public function fetchLabels( array $rootValue, ResolveInfo $info ): Deferred {
		$entityId = ( new BasicEntityIdParser() )->parse( $rootValue['id'] );
		$languageCodes = array_keys( $info->getFieldSelection() );

		$this->entityIdsBatch[] = $entityId;
		$this->languageCodesBatch = array_merge( $this->languageCodesBatch, $languageCodes );

		return new Deferred( function() use( $entityId, $languageCodes ) {
			if ( !$this->hasPrefetched ) {
				$this->termLookup->prefetchTerms(
					array_unique( $this->entityIdsBatch ),
					[ TermTypes::TYPE_LABEL ],
					array_unique( $this->languageCodesBatch )
				);
				$this->hasPrefetched = true;
			}

			return $this->termLookup->getLabels( $entityId, $languageCodes );
		} );
	}
}
