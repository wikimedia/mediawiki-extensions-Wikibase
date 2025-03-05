<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
class ItemRdfBuilder implements EntityRdfBuilder {
	private ?SiteLinksRdfBuilder $siteLinksRdfBuilder;
	private ?TruthyStatementRdfBuilder $truthyStatementRdfBuilder;
	private TermsRdfBuilder $termsRdfBuilder;
	private ?FullStatementRdfBuilder $fullStatementRdfBuilder;

	public function __construct(
		int $flavorFlags,
		SiteLinksRdfBuilder $siteLinksRdfBuilder,
		TermsRdfBuilder $termsRdfBuilder,
		TruthyStatementRdfBuilderFactory $truthyStatementRdfBuilderFactory,
		FullStatementRdfBuilderFactory $fullStatementRdfBuilderFactory
	) {
		$this->siteLinksRdfBuilder = $flavorFlags & RdfProducer::PRODUCE_SITELINKS ? $siteLinksRdfBuilder : null;

		if ( $flavorFlags & RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) {
			$this->truthyStatementRdfBuilder = $truthyStatementRdfBuilderFactory->getTruthyStatementRdfBuilder(
				$flavorFlags
			);
		} else {
			$this->truthyStatementRdfBuilder = null;
		}

		if ( $flavorFlags & RdfProducer::PRODUCE_ALL_STATEMENTS ) {
			$fullStatementRdfBuilder = $fullStatementRdfBuilderFactory->getFullStatementRdfBuilder(
				$flavorFlags
			);
			$this->fullStatementRdfBuilder = $fullStatementRdfBuilder;
		} else {
			$this->fullStatementRdfBuilder = null;
		}

		$this->termsRdfBuilder = $termsRdfBuilder;
	}

	public function addEntity( EntityDocument $entity ) {
		if ( $this->siteLinksRdfBuilder ) {
			$this->siteLinksRdfBuilder->addEntity( $entity );
		}

		if ( $this->truthyStatementRdfBuilder ) {
			$this->truthyStatementRdfBuilder->addEntity( $entity );
		}

		if ( $this->fullStatementRdfBuilder ) {
			$this->fullStatementRdfBuilder->addEntity( $entity );
		}

		if ( $this->termsRdfBuilder ) {
			$this->termsRdfBuilder->addEntity( $entity );
		}
	}
}
