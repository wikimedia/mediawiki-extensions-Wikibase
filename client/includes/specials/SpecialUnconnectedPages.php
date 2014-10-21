<?php

namespace Wikibase\Client\Specials;

use DatabaseBase;
use Html;
use Linker;
use MWException;
use QueryPage;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\NamespaceChecker;

/**
 * List client pages that are not connected to repository items.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialUnconnectedPages extends QueryPage {

	public function __construct() {
		parent::__construct( 'UnconnectedPages' );
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false;
	}
	/**
	 * Title object build from the $startPageName parameter
	 *
	 * @var Title|null
	 */
	private $startTitle = null;

	/**
	 * @var NamespaceChecker|null
	 */
	private $namespaceChecker = null;

	/**
	 * Set to 'only' if the search should only include pages with inter wiki links
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	private $iwData = '';

	/**
	 * Get the NamespaceChecker
	 *
	 * @since 0.4
	 *
	 * @return NamespaceChecker
	 */
	public function getNamespaceChecker() {
		if ( $this->namespaceChecker === null ) {
			$settings = WikibaseClient::getDefaultInstance()->getSettings();

			$this->namespaceChecker = new NamespaceChecker(
				$settings->getSetting( 'excludeNamespaces' ),
				$settings->getSetting( 'namespaces' )
			);
		}

		return $this->namespaceChecker;
	}
	/**
	 * Build conditionals for namespace
	 *
	 * @since 0.4
	 *
	 * @param DatabaseBase $dbr
	 * @param Title $title
	 * @param NamespaceChecker $checker
	 *
	 * @return string[]
	 */
	public function buildConditionals( DatabaseBase $dbr, Title $title = null, NamespaceChecker $checker = null ) {
		$conds = array();

		if ( $title === null ) {
			$title = $this->startTitle;
		}
		if ( $checker === null ) {
			$checker = $this->getNamespaceChecker();
		}
		if ( $title !== null ) {
			$conds[] = 'page_title >= ' . $dbr->addQuotes( $title->getDBkey() );
			$conds[] = 'page_namespace = ' . $title->getNamespace();
		}
		$conds[] = 'page_namespace IN (' . implode( ',', $checker->getWikibaseNamespaces() ) . ')';

		return $conds;
	}

	function getQueryInfo() {
		$dbr = wfGetDB( DB_SLAVE );
		$conds = $this->buildConditionals( $dbr );
		$conds[] = 'page_is_redirect = 0';
		$conds[] = 'pp_propname IS NULL';
		if ( $this->iwData === 'only' ) {
			$conds[] = 'll_from IS NOT NULL';
		}
		$dbrg = array (
			'tables' => array(
				'page',
				'page_props',
				'langlinks'
			),
			'fields' => array(
                                'value' => 'page_id',
				'page_namespace',
				'page_title',
				'page_id',
				'page_len',
				'page_is_redirect',
				'page_num_iwlinks' => 'count(ll_from)'
			),
			'conds' => $conds,
			'options' => array(
				'GROUP BY' => 'page_namespace, page_title',
				'ORDER BY' => 'page_namespace, page_title',
				'USE INDEX' => array( 'page' => 'name_title' )
			),
			'join_conds' => array(
				// FIXME: Should 'wikibase_item' really be hardcoded here?
				'page_props' => array( 'LEFT JOIN', array( 'page_id = pp_page', "pp_propname = 'wikibase_item'" ) ),
				'langlinks' => array( 'LEFT JOIN', 'll_from = page_id' )
			)
		);
		print_r($dbrg);
		return $dbrg;
	}

	function getOrderFields() {
		return array( 'value' );
	}

	function sortDescending() {
		return false;
	}

	function formatResult( $skin, $result ) {
		$title = Title::newFromID( $result->value );
		$link = Linker::linkKnown( $title );
		return $link;
	}

	protected function getGroupName() {
		return 'pages';
	}
}
