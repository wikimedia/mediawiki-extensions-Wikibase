<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\Rdbms\LBFactory;

/**
 * Factory for creating writer objects relating to the 2019 SQL based terms storage.
 *
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactory {

	/**
	 * @var EntitySource
	 */
	private $localEntitySource;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var LBFactory
	 */
	private $loadbalancerFactory;

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
		EntitySource $localEntitySource,
		StringNormalizer $stringNormalizer,
		LBFactory $loadbalancerFactory,
		WANObjectCache $wanCache,
		JobQueueGroup $jobQueueGroup,
		LoggerInterface $logger
	) {
		$this->localEntitySource = $localEntitySource;
		$this->stringNormalizer = $stringNormalizer;
		$this->loadbalancerFactory = $loadbalancerFactory;
		$this->wanCache = $wanCache;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->logger = $logger;
	}

	public function newItemTermStoreWriter(): ItemTermStoreWriter {
		if ( !in_array( Item::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have items.' );
		}

		$typeIdsStore = $this->newTypeIdsStore();
		return new DatabaseItemTermStoreWriter(
			$this->loadbalancerFactory->getMainLB(),
			$this->jobQueueGroup,
			$this->newTermInLangIdsAcquirer( $typeIdsStore ),
			$this->newTermInLangIdsResolver( $typeIdsStore, $typeIdsStore ),
			$this->stringNormalizer
		);
	}

	public function newPropertyTermStoreWriter(): PropertyTermStoreWriter {
		if ( !in_array( Property::ENTITY_TYPE, $this->localEntitySource->getEntityTypes() ) ) {
			throw new LogicException( 'Local entity source does not have properties.' );
		}

		$typeIdsStore = $this->newTypeIdsStore();
		return new DatabasePropertyTermStoreWriter(
			$this->loadbalancerFactory->getMainLB(),
			$this->jobQueueGroup,
			$this->newTermInLangIdsAcquirer( $typeIdsStore ),
			$this->newTermInLangIdsResolver( $typeIdsStore, $typeIdsStore ),
			$this->stringNormalizer
		);
	}

	private function newTermInLangIdsResolver( TypeIdsResolver $typeResolver, TypeIdsLookup $typeLookup ) : TermInLangIdsResolver {
		return new DatabaseTermInLangIdsResolver(
			$typeResolver,
			$typeLookup,
			$this->loadbalancerFactory->getMainLB(),
			false,
			$this->logger
		);
	}

	private function newTermInLangIdsAcquirer( TypeIdsAcquirer $typeAcquirer ) : TermInLangIdsAcquirer {
		return new DatabaseTermInLangIdsAcquirer(
			$this->loadbalancerFactory,
			$typeAcquirer,
			$this->logger
		);
	}

	private function newTypeIdsStore() : DatabaseTypeIdsStore {
		return new DatabaseTypeIdsStore(
			$this->loadbalancerFactory->getMainLB(),
			$this->wanCache
		);
	}

}
