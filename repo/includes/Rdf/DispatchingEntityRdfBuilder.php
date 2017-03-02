<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Dispatching implementation of EntityRdfBuilder. This allows extensions to register
 * EntityRdfBuilders for custom data types.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class DispatchingEntityRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var EntityRdfBuilder[]
	 */
	private $rdfBuilders;

	/**
	 * @param EntityRdfBuilder[] $rdfBuilders EntityRdfBuilder objects keyed by entity type
	 */
	public function __construct( array $rdfBuilders ) {
		foreach ( $rdfBuilders as $key => $value ) {
			Assert::parameterElementType( EntityRdfBuilder::class, $value, '$rdfBuilders' );
		}

		$this->rdfBuilders = $rdfBuilders;
	}

	/**
	 * Adds specific entity
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntity(
		EntityDocument $entity
	) {
		$builders = $this->getRdfBuilders( $entity->getType() );

		if ( $builders ) {
			foreach ( $builders as $builder ) {
				$builder->addEntity( $entity );
			}
		}
	}

	/**
	 * @param string $entityType
	 *
	 * @return null|EntityRdfBuilder[]
	 */
	private function getRdfBuilders( $entityType ) {
		if ( $entityType !== null ) {
			if ( isset( $this->rdfBuilders[$entityType] ) ) {
				return $this->rdfBuilders[$entityType];
			}
		}

		// TODO: Uncomement this when all entity types have rdfBuilders
		// wfLogWarning( __METHOD__ . ": No RDF builder defined for entity type $entityType." );
		return null;
	}

}
