<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class RdfBuilderFactory {

	private RdfVocabulary $vocabulary;
	private EntityRdfBuilderFactory $entityRdfBuilderFactory;
	private EntityContentFactory $entityContentFactory;
	private EntityStubRdfBuilderFactory $entityStubRdfBuilderFactory;
	private EntityRevisionLookup $entityRevisionLookup;

	public function __construct(
		RdfVocabulary $vocabulary,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityContentFactory $entityContentFactory,
		EntityStubRdfBuilderFactory $entityStubRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->entityRdfBuilderFactory = $entityRdfBuilderFactory;
		$this->entityContentFactory = $entityContentFactory;
		$this->entityStubRdfBuilderFactory = $entityStubRdfBuilderFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
	}

	public function getRdfBuilder( int $flavor, DedupeBag $dedupeBag, RdfWriter $rdfWriter ): RdfBuilder {
		return new RdfBuilder(
			$this->vocabulary,
			$this->entityRdfBuilderFactory,
			$flavor,
			$rdfWriter,
			$dedupeBag,
			$this->entityContentFactory,
			$this->entityStubRdfBuilderFactory,
			$this->entityRevisionLookup
		);
	}

}
