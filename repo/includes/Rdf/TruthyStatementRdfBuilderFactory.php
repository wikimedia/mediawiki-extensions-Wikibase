<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class TruthyStatementRdfBuilderFactory {

	private $vocabulary;

	private $writer;

	private $mentionedEntityTracker;

	private $dedupe;

	private $valueSnakRdfBuilderFactory;

	private $propertyDataLookup;

	public function __construct(
		DedupeBag $dedupe,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityMentionListener $mentionedEntityTracker,
		PropertyDataTypeLookup $propertyDataLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->mentionedEntityTracker = $mentionedEntityTracker;
		$this->dedupe = $dedupe;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->propertyDataLookup = $propertyDataLookup;
	}

	public function getTruthyStatementRdfBuilder(
		int $flavorFlags
	): TruthyStatementRdfBuilder {
		$simpleStatementValueBuilder = $this->valueSnakRdfBuilderFactory->getValueSnakRdfBuilder(
			$flavorFlags & ~RdfProducer::PRODUCE_FULL_VALUES, // This is the logic that *really* wants testing
			$this->vocabulary,
			$this->writer,
			$this->mentionedEntityTracker,
			$this->dedupe
		);
		$simpleSnakBuilder = new SnakRdfBuilder( $this->vocabulary, $simpleStatementValueBuilder, $this->propertyDataLookup );
		$simpleSnakBuilder->setEntityMentionListener( $this->mentionedEntityTracker );
		return (
			new TruthyStatementRdfBuilder(
				$this->vocabulary,
				$this->writer,
				$simpleSnakBuilder
			)
		);
	}
}
