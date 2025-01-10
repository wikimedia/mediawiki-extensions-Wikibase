<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\StringNormalizer;

/**
 * Factory for creating writer objects relating to the 2019 SQL based terms storage.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactory {

	private DatabaseEntitySource $localEntitySource;
	private StringNormalizer $stringNormalizer;
	private TermsDomainDb $termsDb;
	private JobQueueGroup $jobQueueGroup;
	private LoggerInterface $logger;

	public function __construct(
		DatabaseEntitySource $localEntitySource,
		StringNormalizer $stringNormalizer,
		TermsDomainDb $termsDb,
		JobQueueGroup $jobQueueGroup,
		LoggerInterface $logger
	) {
		$this->localEntitySource = $localEntitySource;
		$this->stringNormalizer = $stringNormalizer;
		$this->termsDb = $termsDb;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->logger = $logger;
	}

	public function newItemTermStoreWriter(): ItemTermStoreWriter {
		if ( !in_array( Item::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have items.' );
		}

		return new DatabaseItemTermStoreWriter(
			$this->termsDb,
			$this->jobQueueGroup,
			new DatabaseTermInLangIdsAcquirer( $this->termsDb, $this->logger ),
			new DatabaseTermInLangIdsResolver( $this->termsDb, $this->logger ),
			$this->stringNormalizer
		);
	}

	public function newPropertyTermStoreWriter(): PropertyTermStoreWriter {
		if ( !in_array( Property::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have properties.' );
		}

		return new DatabasePropertyTermStoreWriter(
			$this->termsDb,
			$this->jobQueueGroup,
			new DatabaseTermInLangIdsAcquirer( $this->termsDb, $this->logger ),
			new DatabaseTermInLangIdsResolver( $this->termsDb, $this->logger ),
			$this->stringNormalizer
		);
	}

}
