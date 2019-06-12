<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Term ID resolver using the normalized database schema.
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsResolver implements TermIdsResolver {

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var TypeIdsAcquirer */
	private $typeIdsAcquirer;

	/** @var ILoadBalancer */
	private $lb;

	/** @var LoggerInterface */
	private $logger;

	/** @var IDatabase */
	private $dbr = null;

	/** @var string[] stash of data returned from the {@link TypeIdsResolver} */
	private $typeNames = [];

	/**
	 * @param TypeIdsResolver $typeIdsResolver
	 * @param TypeIdsAcquirer $typeIdsAcquirer
	 * @param ILoadBalancer $lb
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		TypeIdsResolver $typeIdsResolver,
		TypeIdsAcquirer $typeIdsAcquirer,
		ILoadBalancer $lb,
		LoggerInterface $logger = null
	) {
		$this->typeIdsResolver = $typeIdsResolver;
		$this->typeIdsAcquirer = $typeIdsAcquirer;
		$this->lb = $lb;
		$this->logger = $logger ?: new NullLogger();
	}

	public function resolveTermIds(
		array $termIds,
		array $types = null,
		array $languages = null
	): array {
		return $this->resolveGroupedTermIds( [ '' => $termIds ], $types, $languages )[''];
	}

	public function resolveGroupedTermIds(
		array $groupedTermIds,
		array $types = null,
		array $languages = null
	): array {
		$groupedTerms = [];

		$groupNamesByTermIds = [];
		foreach ( $groupedTermIds as $groupName => $termIds ) {
			$groupedTerms[$groupName] = [];
			foreach ( $termIds as $termId ) {
				$groupNamesByTermIds[$termId][] = $groupName;
			}
		}
		$allTermIds = array_keys( $groupNamesByTermIds );

		if ( $allTermIds === [] || $types === [] || $languages === [] ) {
			return $groupedTerms;
		}

		$this->logger->debug(
			'{method}: getting {termCount} rows from replica',
			[
				'method' => __METHOD__,
				'termCount' => count( $allTermIds ),
			]
		);
		$replicaResult = $this->selectTerms( $this->getDbr(), $allTermIds, $types, $languages );
		$this->preloadTypes( $replicaResult );
		$replicaTermIds = [];

		foreach ( $replicaResult as $row ) {
			$replicaTermIds[] = $row->wbtl_id;
			foreach ( $groupNamesByTermIds[$row->wbtl_id] as $groupName ) {
				$this->addResultTerms( $groupedTerms[$groupName], $row );
			}
		}

		return $groupedTerms;
	}

	private function selectTerms(
		IDatabase $db,
		array $termIds,
		array $types = null,
		array $languages = null
	): IResultWrapper {
		$additionalConditions = [];
		if ( $languages !== null ) {
			$additionalConditions['wbxl_language'] = $languages;
		}
		if ( $types !== null ) {
			$additionalConditions['wbtl_type_id'] = $this->lookupTypeIds( $types );
		}

		return $db->select(
			[ 'wbt_term_in_lang', 'wbt_text_in_lang', 'wbt_text' ],
			[ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ],
			[
				'wbtl_id' => $termIds,
				// join conditions
				'wbtl_text_in_lang_id=wbxl_id',
				'wbxl_text_id=wbx_id',
			] + $additionalConditions,
			__METHOD__
		);
	}

	private function preloadTypes( IResultWrapper $result ) {
		$typeIds = [];
		foreach ( $result as $row ) {
			$typeId = $row->wbtl_type_id;
			if ( !array_key_exists( $typeId, $this->typeNames ) ) {
				$typeIds[$typeId] = true;
			}
		}
		$this->typeNames += $this->typeIdsResolver->resolveTypeIds( array_keys( $typeIds ) );
	}

	private function addResultTerms( array &$terms, stdClass $row ) {
		$type = $this->lookupTypeName( $row->wbtl_type_id );
		$lang = $row->wbxl_language;
		$text = $row->wbx_text;
		$terms[$type][$lang][] = $text;
	}

	private function lookupTypeName( $typeId ) {
		$typeName = $this->typeNames[$typeId] ?? null;
		if ( $typeName === null ) {
			throw new InvalidArgumentException(
				'Type ID ' . $typeId . ' was requested but not preloaded!' );
		}
		return $typeName;
	}

	private function lookupTypeIds( array $typeNames ) {
		$typeIds = [];
		$additionalTypeIds = [];
		$unknownTypeIds = [];

		foreach ( $typeNames as $typeName ) {
			$typeId = array_search( $typeName, $this->typeNames );
			if ( $typeId === false ) {
				$unknownTypeIds[] = $typeName;
				continue;
			}
			$typeIds[] = $typeId;
		}
		if ( $unknownTypeIds ) {
			$additionalTypeIds = $this->typeIdsAcquirer->acquireTypeIds( $unknownTypeIds );
			$this->typeNames += array_flip( $additionalTypeIds );
		}

		return $typeIds + $additionalTypeIds;
	}

	private function getDbr() {
		if ( $this->dbr === null ) {
			$this->dbr = $this->lb->getConnection( ILoadBalancer::DB_REPLICA );
		}

		return $this->dbr;
	}

}
