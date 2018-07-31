<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Class EntityInfoBuilder implementation relying on database access.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilder extends DBAccessBase implements EntityInfoBuilder {

	/**
	 * Maps term types to fields used for lists of these terms in entity serializations.
	 *
	 * @var string[]
	 */
	private static $termTypeFields = [
		'label' => 'labels',
		'description' => 'descriptions',
	];

	/**
	 * @var string The name of the database table holding terms.
	 */
	private $termTable;

	/**
	 * EntityId objects indexed by serialized ID. This allows us to re-use
	 * the original EntityId object and avoids parsing the string again.
	 *
	 * @var EntityId[]|null map of id-strings to EntityId objects: id-string => EntityId
	 */
	private $entityIds = null;

	/**
	 * The entity info data structure. This data structure is exposed via getEntityInfo().
	 * After resolveRedirects() is called, this will contain entries for the redirect targets
	 * in addition to the entries for the redirected IDs. Entries for the redirected IDs
	 * will be php references to the entries that use the actual (target) IDs as keys.
	 *
	 * @var array[]|null map of id-strings to entity-record arrays:
	 *      id-string => record
	 */
	private $entityInfo = null;

	/**
	 * Maps of ID strings to local ID parts (i.e. excluding the repository prefix, if the
	 * instance is handling entities "foreign" to the local repo (i.e. input entities are prefixed),
	 * group by entity type.
	 * Used to build database queries on tables that use entity ID (as a string). Database used by
	 * the "foreign" repo does not contain prefix in the ID columns that the local repo might be
	 * using for the other repo's entity IDs.
	 *
	 * @var array[]
	 */
	private $localIdsByType = [];

	/**
	 * Maps of id strings to page info records, grouped by entity type.
	 * This uses the same basic structure as $localIdsByType.
	 * Each page info record is an associative array with keys page_id
	 * and redirect_target.
	 *
	 * Initialized lazily by getPageInfoIdsByType().
	 *
	 * @var array[]|null map of entity type to maps of id-strings to numeric ids:
	 *      type => id-string => id-int
	 */
	private $pageInfoByType = null;

	/**
	 * A map of entity id strings to EntityId objects, representing any
	 * redirects present in the list of entities provided to the constructor.
	 *
	 * Initialized lazily by resolveRedirects().
	 *
	 * @var string[]|null map of id-string to EntityId objects:
	 *      id-string => EntityId
	 */
	private $redirects = null;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 * @param string $repositoryName The name of the repository (use an empty string for the local repository)
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		EntityNamespaceLookup $entityNamespaceLookup,
		$wiki = false,
		$repositoryName = ''
	) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		parent::__construct( $wiki );

		$this->termTable = 'wb_terms';

		$this->idParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->repositoryName = $repositoryName;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
	}

	/**
	 * Returns a list of EntityId objects belonging to the repository configured in the constructor.
	 * In other words, this filters out foreign entity IDs, so the builder only processes relevant
	 * EntityIds.
	 *
	 * @param EntityId[] $ids
	 * @return EntityId[]
	 */
	private function filterForeignEntityIds( array $ids ) {
		$repositoryName = $this->repositoryName;

		return array_filter(
			$ids,
			function( EntityId $id ) use ( $repositoryName ) {
				return $id->getRepositoryName() === $repositoryName;
			}
		);
	}

	/**
	 * @param EntityId[] $ids
	 */
	private function setEntityIds( array $ids ) {
		$this->entityIds = [];
		$this->entityInfo = [];
		$this->localIdsByType = [];

		foreach ( $ids as $id ) {
			$this->updateEntityInfo( $id );
		}
	}

	/**
	 * @return EntityInfo
	 */
	private function getEntityInfo() {
		return new EntityInfo( $this->entityInfo );
	}

	private function resolveRedirects() {
		if ( $this->redirects !== null ) {
			// already done
			return;
		}

		$this->redirects = $this->findRedirects();

		foreach ( $this->redirects as $key => $targetId ) {
			$this->applyRedirect( $key, $targetId );
		}
	}

	/**
	 * Applied the given redirect to the internal data structure.
	 *
	 * After this method returns, the old ID will have been replaced by the target ID
	 * in the $entityInfo. In $entityInfo,
	 * the old key will remain as a reference to the entry under the new (target) key.
	 *
	 * @param string $idString The redirected entity id
	 * @param EntityId $targetId The redirect target
	 */
	private function applyRedirect( $idString, EntityId $targetId ) {
		$targetKey = $targetId->getSerialization();

		if ( $idString === $targetKey ) {
			// Sanity check: self-redirect, nothing to do.
			return;
		}

		// Copy the record for the old key to the target key.
		$this->initEntityInfo( $targetKey, $this->entityInfo[$idString] );

		// Remove the original entry for the old key.
		$this->unsetKey( $idString );

		// Make the redirected key a reference to the target record.
		$this->createEntityInfoReference( $idString, $this->entityInfo[$targetKey] );

		// From now on, use the target ID in the record and for database queries.
		$this->updateEntityInfo( $targetId );
	}

	/**
	 * Sets the given key in the $entityInfo data structure to a reference
	 * to the given record. This allows the same record to be accessed
	 * under multiple different keys.
	 *
	 * @param string $key
	 * @param array $record
	 */
	private function createEntityInfoReference( $key, array &$record ) {
		$this->entityInfo[$key] = &$record;
	}

	/**
	 * Removes any references to the given entity from the $entityInfo data
	 * structure, but not from
	 * the $entityIds cache.
	 *
	 * @param string $idString
	 */
	private function unsetKey( $idString ) {
		$id = $this->getEntityId( $idString );

		$type = $id->getEntityType();

		unset( $this->entityInfo[$idString] );
		unset( $this->localIdsByType[$type][$idString] );
	}

	/**
	 * Sets the given key in the $entityInfo data structure to
	 * the given record if that key is not already set.
	 *
	 * @param string $key
	 * @param array $record
	 */
	private function initEntityInfo( $key, array $record ) {
		if ( !isset( $this->entityInfo[$key] ) ) {
			$this->entityInfo[$key] = $record;
		}
	}

	/**
	 * Updates the $entityInfo structure and makes the ID
	 * available via the $entityIds cache.
	 *
	 * @param EntityId $id
	 */
	private function updateEntityInfo( EntityId $id ) {
		$type = $id->getEntityType();
		$key = $id->getSerialization();

		$this->initEntityInfo( $key, [] );

		$this->entityIds[$key] = $id;
		$this->localIdsByType[$type][$key] = $id->getLocalPart();
	}

	/**
	 * Applies a default value to the given field in each entity record.
	 *
	 * @param string $field the field to assign the default value to
	 * @param mixed $default the default value
	 * @param callable|null $filter A filter callback; if given, only records that match
	 *        the filter will be updated. The callback gets the entity record as the only
	 *        parameter, and must return a boolean.
	 */
	private function setDefaultValue( $field, $default, $filter = null ) {
		foreach ( $this->entityInfo as &$entity ) {
			if ( $filter !== null ) {
				$match = call_user_func( $filter, $entity );

				if ( !$match ) {
					continue;
				}
			}

			if ( !isset( $entity[$field] ) ) {
				$entity[$field] = $default;
			}
		}
	}

	/**
	 * @param string[] $languages Which languages to include
	 */
	private function collectTerms( array $languages ) {
		$termTypes = array_keys( self::$termTypeFields );

		foreach ( $termTypes as $type ) {
			$this->setDefaultValue( self::$termTypeFields[$type], [] );
		}

		if ( $languages === [] ) {
			// nothing to do
			return;
		}

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the term_entity_type field.
		foreach ( array_keys( $this->localIdsByType ) as $type ) {
			$this->collectTermsForEntities( $type, $languages );
		}
	}

	/**
	 * Collects the terms for a number of entities (of the given types, in the given languages)
	 *
	 * @param string $entityType
	 * @param string[] $languages
	 */
	private function collectTermsForEntities( $entityType, array $languages ) {
		$termTypes = array_keys( self::$termTypeFields );

		$where = [
			'term_full_entity_id' => $this->localIdsByType[$entityType],
			'term_language' => $languages,
		];

		$fields = [ 'term_type', 'term_language', 'term_text', 'term_full_entity_id' ];

		MediaWikiServices::getInstance()->getStatsdDataFactory()->updateCount(
			'wikibase.repo.wb_terms.select.SqlEntityInfoBuilder_collectTermsForEntities',
			count( $termTypes )
		);
		$closest2Power = round( log( count( $this->localIdsByType[$entityType] ), 2 ) );
		$low = ceil( pow( 2, $closest2Power - 0.5 ) );
		$high = floor( pow( 2, $closest2Power + 0.5 ) );
		$idCount = $low === $high ? $low : "{$low}-{$high}";
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.wb_terms.SqlEntityInfoBuilder_collectTermsForEntities.idCount.' . $idCount
		);

		$dbr = $this->getConnection( DB_REPLICA );

		// Do one query per term type here, this is way faster on MySQL: T147748
		foreach ( $termTypes as $termType ) {
			$res = $dbr->select(
				$this->termTable,
				$fields,
				array_merge( $where, [ 'term_type' => $termType ] ),
				__METHOD__
			);

			$this->injectTerms( $res );
		}

		$this->releaseConnection( $dbr );
	}

	/**
	 * Injects terms from a DB result into the $entityInfo structure.
	 *
	 * @note: Keep in sync with EntitySerializer!
	 *
	 * @param IResultWrapper $dbResult
	 *
	 * @throws InvalidArgumentException
	 */
	private function injectTerms( IResultWrapper $dbResult ) {
		foreach ( $dbResult as $row ) {
			try {
				$entityId = $this->idParser->parse( $row->term_full_entity_id );
			} catch ( EntityIdParsingException $ex ) {
				wfLogWarning( 'Unsupported entity serialization "' . $row->term_full_entity_id . '"' );
				continue;
			}

			$key = $entityId->getSerialization();

			if ( !isset( $this->entityInfo[$key] ) ) {
				continue;
			}

			$field = self::$termTypeFields[$row->term_type];

			switch ( $row->term_type ) {
				case 'label':
					$this->injectLabel( $this->entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				case 'description':
					$this->injectDescription( $this->entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				default:
					wfDebugLog( __CLASS__, __FUNCTION__ . ': unknown term type: ' . $row->term_type );
			}
		}
	}

	/**
	 * @param string[]|null $termList
	 * @param string $language
	 * @param string $text
	 */
	private function injectLabel( &$termList, $language, $text ) {
		$termList[$language] = [
			'language' => $language,
			'value' => $text,
		];
	}

	/**
	 * @param string[]|null $termList
	 * @param string $language
	 * @param string $text
	 */
	private function injectDescription( &$termList, $language, $text ) {
		$termList[$language] = [
			'language' => $language,
			'value' => $text,
		];
	}

	private function removeMissing() {
		$missingIds = $this->getMissingIds();

		$this->unsetEntityInfo( $missingIds );
	}

	/**
	 * Removes the given list of IDs from all internal data structures.
	 *
	 * @param string[] $ids
	 */
	private function unsetEntityInfo( array $ids ) {
		$this->entityInfo = array_diff_key( $this->entityInfo, array_flip( $ids ) );
		$this->entityIds = array_diff_key( $this->entityIds, array_flip( $ids ) );

		foreach ( $this->localIdsByType as &$idsByType ) {
			$idsByType = array_diff_key( $idsByType, array_flip( $ids ) );
		}

		// remove empty entries
		$this->localIdsByType = array_filter( $this->localIdsByType );
	}

	/**
	 * Creates a mapping from the given entity IDs to the corresponding page IDs.
	 *
	 * @param string $entityType
	 *
	 * @return array A map of (numeric) entity IDs to page info record.
	 *         Each page info record is an associative array with the fields
	 *         page_id and redirect_target. Redirects are included.
	 */
	private function getPageInfoForType( $entityType ) {
		if ( isset( $this->pageInfoByType[$entityType] ) ) {
			return $this->pageInfoByType[$entityType];
		}

		$dbr = $this->getConnection( DB_REPLICA );

		$fields = [
			'page_namespace',
			'page_title',
			'page_id',
			'rd_title'
		];

		$res = $dbr->select(
			[ 'page', 'redirect' ],
			$fields,
			[
				'page_namespace' => $this->entityNamespaceLookup->getEntityNamespace( $entityType ),
				'page_title' => $this->localIdsByType[$entityType],
			],
			__METHOD__,
			[],
			[ 'redirect' => [ 'LEFT JOIN', [ 'page_id=rd_from' ] ] ]
		);

		$this->pageInfoByType[$entityType] = [];

		foreach ( $res as $row ) {
			$id = $this->idParser->parse( $row->page_title );
			$idKey = $id->getSerialization();
			$this->pageInfoByType[$entityType][$idKey] = [
				'page_id' => $row->page_id,
				'redirect_target' => $row->rd_title,
			];
		}

		$this->releaseConnection( $dbr );

		return $this->pageInfoByType[$entityType];
	}

	/**
	 * @return array[] Associative array containing a page info record for each entity ID.
	 *         Each page info record is an associative array with the fields
	 *         page_id and redirect_target. Redirects are included.
	 */
	private function getPageInfo() {
		$info = [];

		foreach ( $this->localIdsByType as $type => $ids ) {
			$info[$type] = $this->getPageInfoForType( $type );
		}

		return $this->ungroup( $info );
	}

	/**
	 * Returns an EntityId object for the given serialized ID.
	 * This is implemented as a lookup of the original EntityId object supplied
	 * to the constructor (or found during redirect resolution).
	 *
	 * @param string $idString the serialized id
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException If the ID is malformed.
	 */
	private function getEntityId( $idString ) {
		if ( !isset( $this->entityIds[$idString] ) ) {
			$this->entityIds[$idString] = $this->idParser->parse( $idString );
		}

		return $this->entityIds[$idString];
	}

	/**
	 * Flattens a grouped array structure into a flat array.
	 * Useful e.g. to convert "by type" structures into flat arrays
	 * with ID strings as keys.
	 *
	 * @param array[] $groupedArrays
	 *
	 * @return array
	 */
	private function ungroup( array $groupedArrays ) {
		$merged = array_reduce(
			$groupedArrays,
			function ( $acc, $next ) {
				return array_merge( $acc, $next );
			},
			[]
		);

		return $merged;
	}

	/**
	 * @return string[] The subset of entity ids supplied to the constructor that
	 * do not represent actual entities.
	 */
	private function getMissingIds() {
		$pageInfo = $this->getPageInfo();
		$missingIds = [];

		foreach ( $this->entityInfo as $key => $info ) {
			if ( isset( $pageInfo[$key] ) ) {
				continue;
			}

			$missingIds[] = $key;
		}

		return $missingIds;
	}

	/**
	 * Finds and returns any redirects from the set of entities supplied to the constructor.
	 *
	 * @return EntityId[] An associative array mapping id strings to EntityIds representing
	 * the redirect targets.
	 */
	private function findRedirects() {
		$pageInfo = $this->getPageInfo();
		$redirects = [];

		foreach ( $pageInfo as $key => $pageRecord ) {
			if ( $pageInfo[$key]['redirect_target'] !== null ) {
				$redirects[$key] = $this->getEntityId( $pageInfo[$key]['redirect_target'] );
			}
		}

		return $redirects;
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return string[]
	 */
	private function convertEntityIdsToStrings( array $ids ) {
		return array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $ids );
	}

	/**
	 * Retain only info records for the given EntityIds.
	 * Useful e.g. after resolveRedirects(), to remove explicit entries for
	 * redirect targets not present in the original input.
	 *
	 * @param EntityId[] $ids
	 */
	private function retainEntityInfo( array $ids ) {
		$retain = $this->convertEntityIdsToStrings( $ids );
		$remove = array_diff( array_keys( $this->entityInfo ), $retain );
		$this->unsetEntityInfo( $remove );
	}

	public function collectEntityInfo( array $entityIds, array $languageCodes ) {
		$ids = $this->filterForeignEntityIds( $entityIds );

		$this->setEntityIds( $ids );

		$this->resolveRedirects();

		$this->collectTerms( $languageCodes );

		$this->removeMissing();
		$this->retainEntityInfo( $ids );

		return $this->getEntityInfo();
	}

}
