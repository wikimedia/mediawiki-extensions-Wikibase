<?php

namespace Wikibase\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikimedia\Assert\Assert;

class DispatchingTermBuffer extends EntityTermLookupBase implements TermBuffer {

	/**
	 * @var TermBuffer[]
	 */
	private $termBuffers;

	/**
	 * @param TermBuffer[] $termBuffers
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $termBuffers ) {
		Assert::parameter( !empty( $termBuffers ), '$termBuffers', 'must must not be empty' );
		Assert::parameterElementType( TermBuffer::class, $termBuffers, '$termBuffers' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $termBuffers, '$termBuffers' );
		$this->termBuffers = $termBuffers;
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
		foreach ( $languageCodes as $languageCode ) {
			$terms[$languageCode] = $this->getPrefetchedTerm( $entityId, $termType, $languageCode );
		}

		return $terms;
	}

	/**
	 * @see TermBuffer::prefetchTerms
	 *
	 * @param array $entityIds
	 * @param array|null $termTypes
	 * @param array|null $languageCodes
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$groupedIds = $this->groupEntityIdsByRepo( $entityIds );

		foreach ( $groupedIds as $repository => $ids ) {
			$this->getTermBufferForRepository( $repository )->prefetchTerms(
				array_values( $ids ),
				$termTypes,
				$languageCodes
			);
		}
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return false|null|string
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		return $this->getTermBufferForRepository( $entityId->getRepositoryName() )
			->getPrefetchedTerm( $entityId, $termType, $languageCode );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return array[]
	 */
	private function groupEntityIdsByRepo( array $entityIds ) {
		$entityIdsByRepo = array();

		foreach ( $entityIds as $id ) {
			$repo = $id->getRepositoryName();
			$key = $id->getSerialization();

			$entityIdsByRepo[$repo][$key] = $id;
		}

		return $entityIdsByRepo;
	}

	/**
	 * @param string $repository
	 * @return TermBuffer
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	private function getTermBufferForRepository( $repository ) {
		if ( !isset( $this->termBuffers[$repository] ) ) {
			// TODO: UnknownForeignRepositoryException needs DM-Services 3.7
			throw new UnknownForeignRepositoryException( $repository );
		}
		return $this->termBuffers[$repository];
	}

}
