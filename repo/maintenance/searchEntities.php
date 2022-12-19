<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use MWException;
use OrderedStreamingForkController;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * The script is intended to run searches in the same way as wbsearchentities does.
 * This is mainly intended to test configurations and search options using relforge
 * or analogous tools. It is modeled after runSearch.php script in CirrusSearch extension.
 *
 * The script accepts search requests from stdin, line by line,
 * and outputs results, preserving order.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class SearchEntities extends Maintenance {

	/**
	 * @var EntitySearchHelper
	 */
	private $searchHelper;

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Search entity a-la wbsearchentities API.' );

		$this->addOption( 'entity-type', "Only search this kind of entity, e.g. `item` or `property`.", true, true );
		$this->addOption( 'limit', "Limit how many results are returned. Default is 5.", false, true );
		$this->addOption( 'language', "Language for the search.", true, true );
		$this->addOption( 'display-language', "Language for the display.", false, true );
		$this->addOption( 'strict', "Should we use strict language match?", false, true );
		$this->addOption( 'profile-context', "Profile context for the search context.", false, true );
		$this->addOption( 'engine', "Which engine to use - e.g. sql, elastic.", false, true );
		$this->addOption( 'fork', 'Fork multiple processes to run queries from. Defaults to false.',
			false, true );
		$this->addOption( 'options', 'A JSON object mapping from global variable to its test value',
			false, true );
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$engine = $this->getOption( 'engine', 'sql' );
		$this->searchHelper = $this->getSearchHelper( $engine );

		$callback = [ $this, 'doSearch' ];
		$this->applyGlobals();
		$forks = $this->getOption( 'fork', false );
		$forks = ctype_digit( $forks ) ? intval( $forks ) : 0;
		$controller = new OrderedStreamingForkController( $forks, $callback, STDIN, STDOUT );
		fputs( STDERR, "Please input search terms...\n" );
		fflush( STDERR );
		$controller->start();
	}

	/**
	 * Applies global variables provided as the options CLI argument
	 * to override current settings.
	 * NOTE: this is a hack to test various search profiles, not to be used
	 * to mess with other global variables.
	 */
	protected function applyGlobals() {
		$optionsData = $this->getOption( 'options', 'false' );
		if ( substr_compare( $optionsData, 'B64://', 0, strlen( 'B64://' ) ) === 0 ) {
			$optionsData = base64_decode( substr( $optionsData, strlen( 'B64://' ) ) );
		}
		$options = json_decode( $optionsData, true );
		if ( $options ) {
			foreach ( $options as $key => $value ) {
				if ( array_key_exists( $key, $GLOBALS ) ) {
					$GLOBALS[$key] = $value;
				} else {
					$this->fatalError( "\nERROR: $key is not a valid global variable\n" );
				}
			}
		}
	}

	/**
	 * Run search for one query.
	 * @param string $query
	 * @return string
	 * @throws \Wikibase\Repo\Api\EntitySearchException
	 */
	public function doSearch( $query ) {
		$limit = (int)$this->getOption( 'limit', 5 );

		$results = $this->searchHelper->getRankedSearchResults(
			$query,
			$this->getOption( 'language' ),
			$this->getOption( 'entity-type' ),
			$limit,
			$this->getOption( 'strict', false ),
			$this->getOption( 'profile-context' )
		);
		$out = [
			'query' => $query,
			'totalHits' => count( $results ),
			'rows' => [],
		];

		foreach ( $results as $match ) {
			$entityId = $match->getEntityId();

			$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $entityId );
			$displayLabel = $match->getDisplayLabel();
			$out['rows'][] = [
				'pageId' => $title->getArticleID(),
				'entityId' => $entityId->getSerialization(),
				'title' => $title->getPrefixedText(),
				'snippets' => [
					'term' => $match->getMatchedTerm()->getText(),
					'termLanguage' => $match->getMatchedTerm()->getLanguageCode(),
					'type' => $match->getMatchedTermType(),
					'title' => $displayLabel ? $match->getDisplayLabel()->getText() : "",
					'titleLanguage' => $displayLabel ? $match->getDisplayLabel()->getLanguageCode() : "",
					'text' => $match->getDisplayDescription() ? $match->getDisplayDescription()->getText() : "",
				],
			];
		}
		return json_encode( $out );
	}

	/**
	 * Get appropriate searcher.
	 * @param string $engine
	 * @return EntitySearchHelper
	 * @throws MWException
	 */
	private function getSearchHelper( $engine ) {
		$engines = [
			'sql' => function() {
				return WikibaseRepo::getEntitySearchHelper();
			},
		];

		if ( !isset( $engines[$engine] ) ) {
			throw new MWException( "Unknown engine: $engine, valid values: "
				. implode( ", ", array_keys( $engines ) ) );
		}

		return $engines[$engine]();
	}

}

$maintClass = SearchEntities::class;
require_once RUN_MAINTENANCE_IF_MAIN;
