<?php
namespace Wikibase\Lib\Store;

use CirrusSearch\Connection;
use CirrusSearch\SearchConfig;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\TermIndexEntry;

/**
 * Term lookup using ElasticSearch.
 */
class ElasticTermLookup implements PrefetchingTermLookup {

	/**
	 * @var string[][] Prefetched labels
	 */
	private $labels = [];
	/**
	 * @var string[][] Prefetched descriptions
	 */
	private $descriptions = [];
	/**
	 * @var TermLookupSearcher
	 */
	private $searcher;
	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;
	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct( TermLookupSearcher $search, EntityTitleLookup $titleLookup,
								 EntityIdParser $idParser ) {
		$this->searcher = $search;
		$this->titleLookup = $titleLookup;
		$this->idParser = $idParser;
	}

	/**
	 * Create lookup class from default configs.
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityIdParser $idParser
	 * @return static
	 */
	public static function fromDefaultConfig( EntityTitleLookup $titleLookup, EntityIdParser $idParser ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		$connection = new Connection( $config );
		$searcher = new TermLookupSearcher( $connection, $config->get( SearchConfig::INDEX_BASE_NAME ),
				$config->get( 'CirrusSearchSlowSearch' ),
				$config->getElement( 'CirrusSearchClientSideSearchTimeout', 'default' )
		);
		return new static( $searcher, $titleLookup, $idParser );
	}

	/**
	 * Loads a set of terms into the buffer.
	 * The source from which to fetch would typically be supplied to the buffer's constructor.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes The desired term types; null means all.
	 * @param string[]|null $languageCodes The desired languages; null means all.
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null,
								   array $languageCodes = null ) {
		if ( count( $entityIds ) > TermLookupSearcher::MAX_TITLES_PER_QUERY ) {
			foreach ( array_chunk( $entityIds, TermLookupSearcher::MAX_TITLES_PER_QUERY ) as $chunk ) {
				$this->loadEntities( $chunk );
			}
		} else {
			$this->loadEntities( $entityIds );
		}
	}

	/**
	 * Load data for a set of entities.
	 * TODO: right now always loads all the data, do we need to add any filters?
	 * @param EntityId[] $entityIds
	 */
	private function loadEntities( array $entityIds ) {
		$titles = array_map( function ( EntityId $entityId ) {
			return $this->titleLookup->getTitleForId( $entityId );
		}, $entityIds );

		$sourceFields = [ 'title', 'labels', 'descriptions' ];
		$data = $this->searcher->getByTitle( $titles, $sourceFields );
		if ( !$data->isOK() ) {
			return;
		}
		$found = [];
		foreach ( $data->getValue() as $document ) {
			/**
			 * @var \Elastica\Result $document
			 */
			$sourceData = $document->getSource();
			try {
				$entityId = $this->idParser->parse( $sourceData['title'] );
			} catch ( EntityIdParsingException $e ) {
				// somebody set up us the bad document title?
				continue;
			}
			$key = $entityId->getSerialization();
			$found[$key] = true;
			$this->labels[$key] = array_map( function ( $v ) {
				if ( is_array( $v ) ) {
					return $v[0];
				}
				return $v;
			}, $sourceData['labels'] );

			$this->descriptions[$key] = $sourceData['descriptions'];
		}

		// Record the fact that we tried some IDs and fetch failed,
		// so we do not try to fetch them repeatedly.
		foreach ( $entityIds as $entityId ) {
			$key = $entityId->getSerialization();
			if ( empty( $found[$key] ) ) {
				$this->labels[$key] = null;
				$this->descriptions[$key] = null;
			}
		}
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
		try {
			switch ( $termType ) {
				case TermIndexEntry::TYPE_LABEL:
					$result = $this->getTerms( 'labels', $entityId, [ $languageCode ], true );
					break;
				case TermIndexEntry::TYPE_DESCRIPTION:
					$result = $this->getTerms( 'descriptions', $entityId, [ $languageCode ], true );
					break;
				default:
					throw new \InvalidArgumentException( "Not defined for \$termType \"$termType\"" );
			}
		} catch ( TermLookupException $e ) {
			return false;
		}
		if ( empty( $result ) ) {
			return null;
		}
		if ( empty( $result[$languageCode] ) ) {
			return false;
		}
		return reset( $result );
	}

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null Label for specific language, or null if entity has no label in this language.
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$result = $this->getTerms( 'labels', $entityId, [ $languageCode ] );

		if ( empty( $result ) ) {
			return null;
		}

		return reset( $result );
	}

	/**
	 * Fetch terms from source, by ID and language(s).
	 * @param string $type Type of the fetch - 'labels' or 'descriptions'
	 * @param EntityId $entityId
	 * @param string[] $languageCodes
	 * @param bool $noFetch Set if only pre-loaded cache should be used.
	 * @return string[]|null Null if fetch is prohibited and this item wasn't fetched
	 */
	private function getTerms( $type, EntityId $entityId, array $languageCodes, $noFetch = false ) {
		if ( $entityId->isForeign() ) {
			throw new TermLookupException( $entityId, $languageCodes,
				"Foreign entities not supported yet." );
		}
		$entityKey = $entityId->getSerialization();
		// Check if we never tried to load it, then give it a try.
		// TODO: make it possible to load per-language
		if ( !array_key_exists( $entityKey, $this->$type ) ) {
			if ( $noFetch ) {
				return null;
			}
			$this->loadEntities( [ $entityId ] );
		}
		$typeData = $this->$type;
		if ( !isset( $typeData[$entityKey] ) ) {
			// We know this entity does not exist because we tried to fetch it before
			throw new TermLookupException( $entityId, $languageCodes );
		}
		$data = [];
		foreach ( $languageCodes as $lc ) {
			if ( isset( $typeData[$entityKey][$lc] ) ) {
				$data[$lc] = $typeData[$entityKey][$lc];
			}
		}
		return $data;
	}

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * The result will contain the entries for the requested languages, if they exist.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] labels, keyed by language.
	 */
	public function getLabels( EntityId $entityId,
							   array $languageCodes ) {
		return $this->getTerms( 'labels', $entityId, $languageCodes );
	}

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null Description for specific language, or null if entity has none in this language.
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$result = $this->getTerms( 'descriptions', $entityId, [ $languageCode ] );

		if ( empty( $result ) ) {
			return null;
		}

		return reset( $result );
	}

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * If $languages is given, the result will contain the entries for the
	 * requested languages, if they exist.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] descriptions, keyed by language.
	 */
	public function getDescriptions( EntityId $entityId,
									 array $languageCodes ) {
		return $this->getTerms( 'descriptions', $entityId, $languageCodes );
	}

}
