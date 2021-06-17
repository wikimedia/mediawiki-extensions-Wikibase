<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class PropertyRdfBuilder implements EntityRdfBuilder {

	private $truthyStatementRdfBuilder;
	private $fullStatementRdfBuilder;
	private $termsRdfBuilder;
	private $propertySpecificComponentsRdfBuilder;

	public function __construct(
		int $flavorFlags,
		TruthyStatementRdfBuilderFactory $truthyStatementRdfBuilderFactory,
		FullStatementRdfBuilderFactory $fullStatementRdfBuilderFactory,
		TermsRdfBuilder $termsRdfBuilder,
		PropertySpecificComponentsRdfBuilder $propertySpecificComponentsRdfBuilder
	) {

		if ( $flavorFlags & RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) {
			$this->truthyStatementRdfBuilder = $truthyStatementRdfBuilderFactory->getTruthyStatementRdfBuilder(
				$flavorFlags
			);
		}

		if ( $flavorFlags & RdfProducer::PRODUCE_ALL_STATEMENTS ) {
			$fullStatementRdfBuilder = $fullStatementRdfBuilderFactory->getFullStatementRdfBuilder(
				$flavorFlags
			);
			$this->fullStatementRdfBuilder = $fullStatementRdfBuilder;
		}
		$this->termsRdfBuilder = $termsRdfBuilder;
		$this->propertySpecificComponentsRdfBuilder = $propertySpecificComponentsRdfBuilder;
	}

	public function addEntity( EntityDocument $entity ): void {
		if ( $this->truthyStatementRdfBuilder ) {
			$this->truthyStatementRdfBuilder->addEntity( $entity );
		}

		if ( $this->fullStatementRdfBuilder ) {
			$this->fullStatementRdfBuilder->addEntity( $entity );
		}
		$this->termsRdfBuilder->addEntity( $entity );

		$this->propertySpecificComponentsRdfBuilder->addEntity( $entity );
	}
}
