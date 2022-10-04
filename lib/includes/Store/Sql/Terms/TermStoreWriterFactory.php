<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\StringNormalizer;

/**
 * Factory for creating writer objects relating to the 2019 SQL based terms storage.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactory {

	/**
	 * @var DatabaseEntitySource
	 */
	private $localEntitySource;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/** @var TypeIdsAcquirer */
	private $typeIdsAcquirer;

	/** @var TypeIdsLookup */
	private $typeIdsLookup;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/**
	 * @var RepoDomainDb
	 */
	private $repoDb;

	/**
	 * @var WANObjectCache
	 */
	private $wanCache;

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		DatabaseEntitySource $localEntitySource,
		StringNormalizer $stringNormalizer,
		TypeIdsAcquirer $typeIdsAcquirer,
		TypeIdsLookup $typeIdsLookup,
		TypeIdsResolver $typeIdsResolver,
		RepoDomainDb $repoDb,
		WANObjectCache $wanCache,
		JobQueueGroup $jobQueueGroup,
		LoggerInterface $logger
	) {
		$this->localEntitySource = $localEntitySource;
		$this->stringNormalizer = $stringNormalizer;
		$this->typeIdsAcquirer = $typeIdsAcquirer;
		$this->typeIdsLookup = $typeIdsLookup;
		$this->typeIdsResolver = $typeIdsResolver;
		$this->repoDb = $repoDb;
		$this->wanCache = $wanCache;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->logger = $logger;
	}

	public function newItemTermStoreWriter(): ItemTermStoreWriter {
		if ( !in_array( Item::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have items.' );
		}

		return new DatabaseItemTermStoreWriter(
			$this->repoDb,
			$this->jobQueueGroup,
			$this->newTermInLangIdsAcquirer( $this->typeIdsAcquirer ),
			$this->newTermInLangIdsResolver( $this->typeIdsResolver, $this->typeIdsLookup ),
			$this->stringNormalizer
		);
	}

	public function newPropertyTermStoreWriter(): PropertyTermStoreWriter {
		if ( !in_array( Property::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have properties.' );
		}

		return new DatabasePropertyTermStoreWriter(
			$this->repoDb,
			$this->jobQueueGroup,
			$this->newTermInLangIdsAcquirer( $this->typeIdsAcquirer ),
			$this->newTermInLangIdsResolver( $this->typeIdsResolver, $this->typeIdsLookup ),
			$this->stringNormalizer
		);
	}

	private function newTermInLangIdsResolver( TypeIdsResolver $typeResolver, TypeIdsLookup $typeLookup ): TermInLangIdsResolver {
		return new DatabaseTermInLangIdsResolver(
			$typeResolver,
			$typeLookup,
			$this->repoDb,
			$this->logger
		);
	}

	private function newTermInLangIdsAcquirer( TypeIdsAcquirer $typeAcquirer ): TermInLangIdsAcquirer {
		return new DatabaseTermInLangIdsAcquirer(
			$this->repoDb,
			$typeAcquirer,
			$this->logger
		);
	}

}
