<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DataModel\Term\Term;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Quick'n'dirty custom version of DispatchingTermLookup to
 * have a service implementing both Lookup and Buffer interfaces
 *
 * @since 3.7
 *
 * @license GPL-2.0+
 */
class DispatchingBufferingTermLookup implements TermLookup, TermBuffer {

	/**
	 * @var TermLookup[]
	 * @var TermBuffer[]
	 */
	private $lookups;

	/**
	 * @param TermLookup[] $lookups associative array with repository names (strings) as keys
	 *                              and TermLookup objects as values. Empty-string key
	 *                              defines lookup for the local repository.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $lookups ) {
		Assert::parameter(
			!empty( $lookups ) && array_key_exists( '', $lookups ),
			'$lookups',
			'must must not be empty and must contain an empty-string key'
		);
		Assert::parameterElementType( TermLookup::class, $lookups, '$lookups' );
		Assert::parameterElementType( TermBuffer::class, $lookups, '$lookups' );
		Assert::parameterElementType( 'string', array_keys( $lookups ), 'array_keys( $lookups )' );
		foreach ( array_keys( $lookups ) as $repositoryName ) {
			Assert::parameter(
				strpos( $repositoryName, ':' ) === false,
				'array_keys( $lookups )',
				'must not contain strings including colons'
			);
		}
		$this->lookups = $lookups;
	}

	/**
	 * @see TermLookup::getLabel
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @throws UnknownForeignRepositoryException
	 *
	 * @return null|string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->getLookupForEntityId( $entityId )->getLabel( $entityId, $languageCode );
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes
	 *
	 * @throws TermLookupException
	 * @throws UnknownForeignRepositoryException
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return $this->getLookupForEntityId( $entityId )->getLabels( $entityId, $languageCodes );
	}

	/**
	 * @see TermLookup::getDescription
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @throws UnknownForeignRepositoryException
	 *
	 * @return null|string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->getLookupForEntityId( $entityId )->getDescription( $entityId, $languageCode );
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes
	 *
	 * @throws TermLookupException
	 * @throws UnknownForeignRepositoryException
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->getLookupForEntityId( $entityId )->getDescriptions( $entityId, $languageCodes );
	}

	/**
	 * @param EntityId $entityId
	 * @return TermLookup|TermBuffer
	 */
	private function getLookupForEntityId( EntityId $entityId ) {
		$repo = $entityId->getRepositoryName();
		if ( !isset( $this->lookups[$repo] ) ) {
			throw new UnknownForeignRepositoryException( $repo );
		}
		return $this->lookups[$repo];
	}

	/**
	 * see TermBuffer::prefetchTerms
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes The desired term types; null means all.
	 * @param string[]|null $languageCodes The desired languages; null means all.
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$entityIdsByRepo = [];
		// TODO: do some fancy functional magic here ?
		foreach ( $entityIds as $entityId ) {
			$repo = $entityId->getRepositoryName();
			if ( !array_key_exists( $repo, $entityIdsByRepo ) ) {
				$entityIdsByRepo[$repo] = [];
			}
			$entityIdsByRepo[$repo][] = $entityId;
		}
		foreach ( $entityIdsByRepo as $repo => $ids ) {
			$this->getBufferForRepo( $repo )->prefetchTerms( $ids, $termTypes, $languageCodes );
		}
	}

	/**
	 * @param string $repo
	 * @return TermBuffer|TermLookup
	 */
	private function getBufferForRepo( $repo ) {
		if ( !isset( $this->lookups[$repo] ) ) {
			throw new UnknownForeignRepositoryException( $repo );
		}
		return $this->lookups[$repo];
	}

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false of that term is known to not exist,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$this->getLookupForEntityId( $entityId )->getPrefetchedTerm( $entityId, $termType, $languageCode );
	}

}
