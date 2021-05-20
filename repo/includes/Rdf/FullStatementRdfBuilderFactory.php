<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class FullStatementRdfBuilderFactory {

	private $vocabulary;

	private $writer;

	private $mentionedEntityTracker;

	private $dedupe;

	private $valueSnakRdfBuilderFactory;

	private $propertyDataLookup;

	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityMentionListener $mentionedEntityTracker,
		DedupeBag $dedupe,
		PropertyDataTypeLookup $propertyDataLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->mentionedEntityTracker = $mentionedEntityTracker;
		$this->dedupe = $dedupe;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->propertyDataLookup = $propertyDataLookup;
	}

	public function getFullStatementRdfBuilder(
		int $flavorFlags
	): FullStatementRdfBuilder {
		$statementValueBuilder = $this->valueSnakRdfBuilderFactory->getValueSnakRdfBuilder(
			$flavorFlags,
			$this->vocabulary,
			$this->writer,
			$this->mentionedEntityTracker,
			$this->dedupe
		);
		$snakBuilder = $this->getSnakRdfBuilder(
			$statementValueBuilder,
			$this->propertyDataLookup
		);
		$snakBuilder->setEntityMentionListener( $this->mentionedEntityTracker );

		$fullStatementRdfBuilder = new FullStatementRdfBuilder( $this->vocabulary,
			$this->writer,
			$snakBuilder
 );

		$fullStatementRdfBuilder->setDedupeBag( $this->dedupe );

		$fullStatementRdfBuilder->setProduceQualifiers( ( $flavorFlags & RdfProducer::PRODUCE_QUALIFIERS ) !== 0 );
		$fullStatementRdfBuilder->setProduceReferences( ( $flavorFlags & RdfProducer::PRODUCE_REFERENCES ) !== 0 );

		return $fullStatementRdfBuilder;
	}

	private function getSnakRdfBuilder(
		DispatchingValueSnakRdfBuilder $statementValueBuilder,
		PropertyDataTypeLookup $propertyDataLookup
	): SnakRdfBuilder {
		$snakBuilder = new SnakRdfBuilder(
			$this->vocabulary,
			$statementValueBuilder,
			$propertyDataLookup
		);

		return $snakBuilder;
	}

}
