<?php
namespace Wikibase\Lib\Store;

use CirrusSearch\Connection;
use CirrusSearch\Searcher;
use ConfigException;
use LinkBatch;
use MediaWiki\MediaWikiServices;
use MWException;
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
	 * @var Searcher
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

	/**
	 * ElasticTermLookup constructor.
	 * @param Searcher $search
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityIdParser $idParser
	 */
	public function __construct( Searcher $search, EntityTitleLookup $titleLookup,
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
	 * @throws ConfigException
	 */
	public static function fromDefaultConfig( EntityTitleLookup $titleLookup, EntityIdParser $idParser ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		$connection = new Connection( $config );
		$searcher = new Searcher( $connection, 0, -1, $config );
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
		$this->loadEntities( $entityIds );
	}

	/**
	 * Load data for a set of entities.
	 * TODO: right now always loads all the data, do we need to add any filters?
	 * @param EntityId[] $entityIds
	 */
	private function loadEntities( array $entityIds ) {
		$linkBatch = new LinkBatch();

		foreach ( $entityIds as $entityId ) {
			try {
				$linkBatch->addObj( $this->titleLookup->getTitleForId( $entityId ) );
			} catch ( MWException $e ) {
				// Ignore bad entity IDs, provided such ones exist
				continue;
			}
		}
		$pages = $linkBatch->doQuery();

		if ( $pages === false ) {
			return;
		}

		$ns = []; $docIds = [];
		foreach ( $pages as $page ) {
			$docIds[] = $page->page_id;
			$ns[$page->page_namespace] = true;
		}

		$this->searcher->getSearchContext()->setNamespaces( array_keys( $ns ) );

		$sourceFields = [ 'title', 'labels', 'descriptions' ];
		$data = $this->searcher->get( $docIds, $sourceFields );
		if ( !$data->isOK() ) {
			return;
		}
		$found = [];
		foreach ( $data->getValue() as $document ) {
			/**
			 * @var \Elastica\Result $document
			 */
			$sourceData = $document->getData();
			try {
				$entityId = $this->idParser->parse( $sourceData['title'] );
			} catch ( EntityIdParsingException $e ) {
				// somebody set up us the bad document title?
				continue;
			}
			$key = $entityId->getSerialization();
			$found[$key] = true;
			$this->labels[$key] = $sourceData['labels'];
			$this->descriptions[$key] = $sourceData['descriptions'];
		}

		// Record the fact that we tried some IDs and fetch failed,
		// so we do not try to fetch them repeatedly.
		foreach ( $entityIds as $entityId ) {
			$key = $entityId->getSerialization();
			if ( !$found[$key] ) {
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
		switch ( $termType ) {
			case TermIndexEntry::TYPE_LABEL:
				return $this->getLabel( $entityId, $languageCode );
			case TermIndexEntry::TYPE_DESCRIPTION:
				return $this->getDescription( $entityId, $languageCode );
			default:
				throw new \InvalidArgumentException( "Not defined for \$termType \"$termType\"" );
		}
	}

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$result = $this->getTerms( $this->labels, $entityId, [ $languageCode ] );

		if ( empty( $result ) ) {
			return null;
		}

		return reset( $result );
	}

	/**
	 * Fetch terms from source, by ID and language(s).
	 * @param string[][] $source
	 * @param EntityId $entityId
	 * @param string[] $languageCodes
	 * @throws TermLookupException for entity not found
	 * @return string[]
	 */
	private function getTerms( array $source, EntityId $entityId, array $languageCodes ) {
		if ( $entityId->isForeign() ) {
			throw new TermLookupException( $entityId, $languageCodes,
				"Foreign entities not supported yet." );
		}
		$entityKey = $entityId->getSerialization();
		// Check if we never tried to load it, then give it a try.
		if ( !array_key_exists( $entityKey, $source ) ) {
			$this->loadEntities( [ $entityId ] );
		}
		if ( !isset( $source[$entityKey] ) ) {
			throw new TermLookupException( $entityId, $languageCodes );
		}
		$data = [];
		foreach ( $languageCodes as $lc ) {
			if ( isset( $source[$entityKey][$lc] ) ) {
				$data[$lc] = $source[$entityKey][$lc];
			}
		}
		return $data;
	}

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * The result will contain the entries for the requested languages, if they exist.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] labels, keyed by language.
	 */
	public function getLabels( EntityId $entityId,
							   array $languageCodes ) {
		return $this->getTerms( $this->labels, $entityId, $languageCodes );
	}

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$result = $this->getTerms( $this->descriptions, $entityId, [ $languageCode ] );

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
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] descriptions, keyed by language.
	 */
	public function getDescriptions( EntityId $entityId,
									 array $languageCodes ) {
		return $this->getTerms( $this->descriptions, $entityId, $languageCodes );
	}

}
