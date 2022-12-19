<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Language;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Serializers\Serializer;
use Wikibase\Client\Serializer\ClientEntitySerializer;
use Wikibase\Client\Serializer\ClientStatementListSerializer;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * Functionality needed to expose Entities to Lua.
 *
 * @license GPL-2.0-or-later
 */
class EntityAccessor {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var Serializer
	 */
	private $statementSerializer;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $termFallbackChain;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param Serializer $entitySerializer
	 * @param Serializer $statementSerializer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param TermLanguageFallbackChain $termFallbackChain
	 * @param Language $language
	 * @param ContentLanguages $termsLanguages
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator,
		Serializer $entitySerializer,
		Serializer $statementSerializer,
		PropertyDataTypeLookup $dataTypeLookup,
		TermLanguageFallbackChain $termFallbackChain,
		Language $language,
		ContentLanguages $termsLanguages,
		LoggerInterface $logger = null
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->entitySerializer = $entitySerializer;
		$this->statementSerializer = $statementSerializer;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->termFallbackChain = $termFallbackChain;
		$this->language = $language;
		$this->termsLanguages = $termsLanguages;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * Recursively renumber a serialized array in place, so it is indexed at 1, not 0.
	 * Just like Lua wants it.
	 *
	 * @param array &$entityArr
	 */
	private function renumber( array &$entityArr ) {
		foreach ( $entityArr as &$value ) {
			if ( !is_array( $value ) ) {
				continue;
			}
			if ( isset( $value[0] ) ) {
				array_unshift( $value, null );
				unset( $value[0] );
			}
			$this->renumber( $value );
		}
	}

	/**
	 * Get entity from prefixed ID (e.g. "Q23") and return it as serialized array.
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array|null
	 */
	public function getEntity( $prefixedEntityId ) {
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		try {
			$entityObject = $this->entityLookup->getEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			$this->logPossibleDoubleRedirect( $prefixedEntityId );

			return null;
		}

		if ( $entityObject === null ) {
			return null;
		}

		$entityArr = $this->newClientEntitySerializer()->serialize( $entityObject );

		// Renumber the entity as Lua uses 1-based array indexing
		$this->renumber( $entityArr );
		$entityArr['schemaVersion'] = 2;

		return $entityArr;
	}

	/**
	 * Find out whether an entity exists.
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return bool
	 */
	public function entityExists( $prefixedEntityId ) {
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		// This doesn't really depend on any aspect of the entity specifically.
		$this->usageAccumulator->addOtherUsage( $entityId );
		try {
			return $this->entityLookup->hasEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			$this->logPossibleDoubleRedirect( $prefixedEntityId );

			return false;
		}
	}

	/**
	 * Get statement list from prefixed ID (e.g. "Q23") and property (e.g "P123") and return it as serialized array.
	 *
	 * @param string $prefixedEntityId
	 * @param string $propertyIdSerialization
	 * @param string $rank Which statements to include. Either "best" or "all".
	 *
	 * @return array|null
	 */
	public function getEntityStatements( $prefixedEntityId, $propertyIdSerialization, $rank ) {
		$prefixedEntityId = trim( $prefixedEntityId );
		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		$propertyId = new NumericPropertyId( $propertyIdSerialization );
		$this->usageAccumulator->addStatementUsage( $entityId, $propertyId );

		try {
			$entity = $this->entityLookup->getEntity( $entityId );
		} catch ( UnresolvedEntityRedirectException $ex ) {
			$this->logPossibleDoubleRedirect( $prefixedEntityId );

			return null;
		}

		if ( !( $entity instanceof StatementListProvider ) ) {
			return null;
		}

		$statements = $entity->getStatements()->getByPropertyId( $propertyId );

		if ( $rank === 'best' ) {
			$statements = $statements->getBestStatements();
		} elseif ( $rank !== 'all' ) {
			throw new InvalidArgumentException( '$rank must be "best" or "all", "' . $rank . '" given' );
		}

		$serialization = $this->newClientStatementListSerializer()->serialize( $statements );
		$this->renumber( $serialization );
		return $serialization;
	}

	private function newClientEntitySerializer() {
		return new ClientEntitySerializer(
			$this->entitySerializer,
			$this->dataTypeLookup,
			$this->entityIdParser,
			array_unique( array_merge(
				$this->termsLanguages->getLanguages(),
				$this->termFallbackChain->getFetchLanguageCodes(),
				[ $this->language->getCode() ]
			) ),
			[ $this->language->getCode() => $this->termFallbackChain ]
		);
	}

	private function newClientStatementListSerializer() {
		return new ClientStatementListSerializer(
			$this->statementSerializer,
			$this->dataTypeLookup,
			$this->entityIdParser
		);
	}

	/**
	 * @see UnresolvedEntityRedirectException
	 * @param $prefixedEntityId
	 */
	private function logPossibleDoubleRedirect( $prefixedEntityId ) {
		$this->logger->info( 'Unresolved redirect encountered loading {prefixedEntityId}. This is typically cleaned up asynchronously.',
			[
				'prefixedEntityId' => $prefixedEntityId,
			]
		);
	}

}
