<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class DispatchingTermBuffer extends EntityTermLookupBase implements PrefetchingTermLookup {

	/**
	 * @var TermBuffer[]
	 */
	private $termBuffers;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param TermBuffer[] $termBuffers
	 * @param LoggerInterface $logger
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $termBuffers, LoggerInterface $logger ) {
		Assert::parameter( !empty( $termBuffers ), '$termBuffers', 'must not be empty' );
		Assert::parameterElementType( TermBuffer::class, $termBuffers, '$termBuffers' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $termBuffers, '$termBuffers' );
		$this->termBuffers = $termBuffers;
		$this->logger = $logger;
	}

	/**
	 * @see EntityTermLookupBase::getTermsOfType
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[] $languageCodes
	 *
	 * @return string[]
	 */
	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ) {
		$this->prefetchTerms( [ $entityId ], [ $termType ], $languageCodes );

		$terms = [];
		foreach ( $languageCodes as $lang ) {
			$terms[$lang] = $this->getPrefetchedTerm( $entityId, $termType, $lang );
		}

		return $this->stripUndefinedTerms( $terms );
	}

	/**
	 * Remove all non-string entries from an array.
	 * Useful for getting rid of negative cache entries.
	 *
	 * @param string[] $terms
	 *
	 * @return string[]
	 */
	private function stripUndefinedTerms( array $terms ) {
		return array_filter( $terms, 'is_string' );
	}

	/**
	 * @see TermBuffer::prefetchTerms
	 *
	 * @param array $entityIds
	 * @param array|null $termTypes
	 * @param array|null $languageCodes
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$groupedIds = $this->groupEntityIdsByRepo( $entityIds );

		foreach ( $groupedIds as $repository => $ids ) {
			$termBuffer = $this->getTermBufferForRepository( $repository );

			if ( $termBuffer === null ) {
				$this->logger->debug(
					'{method}: unknown repository: {repository}',
					[
						'method' => __METHOD__,
						'repository' => $repository,
					]
				);
				continue;
			}

			$termBuffer->prefetchTerms(
				$ids,
				$termTypes,
				$languageCodes
			);
		}
	}

	/**
	 * @see TermBuffer::getPrefetchedTerm
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$termBuffer = $this->getTermBufferForRepository( $entityId->getRepositoryName() );
		return $termBuffer !== null ?
			$termBuffer->getPrefetchedTerm( $entityId, $termType, $languageCode )
			: null;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return array[]
	 */
	private function groupEntityIdsByRepo( array $entityIds ) {
		$entityIdsByRepo = [];

		foreach ( $entityIds as $id ) {
			$repo = $id->getRepositoryName();
			$entityIdsByRepo[$repo][] = $id;
		}

		return $entityIdsByRepo;
	}

	/**
	 * @param string $repository
	 *
	 * @return TermBuffer
	 */
	private function getTermBufferForRepository( $repository ) {
		return isset( $this->termBuffers[$repository] ) ?
			$this->termBuffers[$repository]
			: null;
	}

}
