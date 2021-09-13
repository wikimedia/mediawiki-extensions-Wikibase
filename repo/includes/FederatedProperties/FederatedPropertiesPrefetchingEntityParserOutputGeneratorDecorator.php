<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;

/**
 * Wraps an EntityParserOutputGenerator and prefetches data for Federated Properties used on the given Entity.
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator implements EntityParserOutputGenerator {

	/**
	 * @var EntityParserOutputGenerator
	 */
	private $inner;

	/**
	 * @var ApiEntityLookup
	 */
	private $apiEntityLookup;

	public function __construct(
		EntityParserOutputGenerator $inner,
		ApiEntityLookup $apiEntityLookup
	) {
		$this->inner = $inner;
		$this->apiEntityLookup = $apiEntityLookup;
	}

	/**
	 * @param EntityRevision $entityRevision
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 * @throws FederatedPropertiesError|FederatedPropertiesException
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		$generateHtml = true
	) {
		$this->prefetchFederatedProperties( $entityRevision->getEntity() );

		return $this->inner->getParserOutput( $entityRevision, $generateHtml );
	}

	private function prefetchFederatedProperties( EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return;
		}

		$propertyIds = array_map( function( $snak ) {
			return $snak->getPropertyId();
		}, $entity->getStatements()->getAllSnaks() );

		$federatedPropertyIds = array_filter(
			$propertyIds,
			function ( $propId ) {
				// TODO: after T288234 is resolved, consider more flexible filtering by type.
				return $propId instanceof FederatedPropertyId;
			}
		);
		'@phan-var FederatedPropertyId[] $federatedPropertyIds';

		$this->apiEntityLookup->fetchEntities( array_unique( $federatedPropertyIds ) );
	}

}
