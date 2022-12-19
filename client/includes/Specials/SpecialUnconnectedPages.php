<?php

namespace Wikibase\Client\Specials;

use Html;
use NamespaceInfo;
use QueryPage;
use Skin;
use TitleFactory;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * List client pages that are not connected to repository items.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Amir Sarabadani < ladsgroup@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialUnconnectedPages extends QueryPage {

	/**
	 * @var int maximum supported offset
	 */
	private const MAX_OFFSET = 10000;

	/** @var NamespaceInfo */
	private $namespaceInfo;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var NamespaceChecker */
	private $namespaceChecker;

	/** @var ClientDomainDb */
	private $db;

	public function __construct(
		NamespaceInfo $namespaceInfo,
		TitleFactory $titleFactory,
		ClientDomainDbFactory $db,
		NamespaceChecker $namespaceChecker
	) {
		parent::__construct( 'UnconnectedPages' );
		$this->namespaceInfo = $namespaceInfo;
		$this->titleFactory = $titleFactory;
		$this->namespaceChecker = $namespaceChecker;
		$this->db = $db->newLocalDb();
		$this->setDBLoadBalancer( $this->db->loadBalancer() );
	}

	/**
	 * @see QueryPage::isSyndicated
	 *
	 * @return bool Always false because we do not want to build RSS/Atom feeds for this page.
	 */
	public function isSyndicated() {
		return false;
	}

	/**
	 * @see QueryPage::isCacheable
	 *
	 * @return bool Always false as our query can be parameterized (by namespace), and QueryPage
	 *     doesn't support that.
	 */
	public function isCacheable() {
		return false;
	}

	/**
	 * Build conditionals for namespace
	 */
	public function buildNamespaceConditionals(): array {
		$conds = [];

		$wbNamespaces = $this->namespaceChecker->getWikibaseNamespaces();
		$ns = $this->getRequest()->getIntOrNull( 'namespace' );

		if ( $ns !== null && in_array( $ns, $wbNamespaces ) ) {
			$conds['pp_sortkey'] = -$ns;
		} else {
			$conds['pp_sortkey'] = [];
			foreach ( $wbNamespaces as $wbNs ) {
				$conds['pp_sortkey'][] = -$wbNs;
			}
		}

		return $conds;
	}

	/**
	 * @see QueryPage::getQueryInfo
	 *
	 * @return array[]
	 */
	public function getQueryInfo() {
		return [
			'tables' => [
				'page',
				'page_props',
			],
			'fields' => [
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
			],
			'conds' => $this->buildNamespaceConditionals(),
			// Sorting is determined getOrderFields()
			'options' => [],
			'join_conds' => [
				'page_props' => [
					'INNER JOIN',
					[ 'page_id = pp_page', 'pp_propname' => 'unexpectedUnconnectedPage' ],
				],
			],
		];
	}

	/**
	 * @return string[]
	 */
	protected function getOrderFields() {
		// Should make use of the "pp_propname_sortkey_page" index.
		return [ 'pp_sortkey', 'page_id' ];
	}

	protected function sortDescending(): bool {
		return true;
	}

	/**
	 * @see QueryPage::reallyDoQuery
	 *
	 * @param int|bool $limit
	 * @param int|bool $offset
	 *
	 * @return IResultWrapper
	 */
	public function reallyDoQuery( $limit, $offset = false ) {
		if ( is_int( $offset ) && $offset > self::MAX_OFFSET ) {
			return new FakeResultWrapper( [] );
		}

		return parent::reallyDoQuery( $limit, $offset );
	}

	/**
	 * @see QueryPage::formatResult
	 *
	 * @param Skin $skin
	 * @param object $result
	 *
	 * @return string|bool
	 */
	public function formatResult( $skin, $result ) {
		$title = $this->titleFactory->newFromID( $result->value );
		if ( $title === null ) {
			return false;
		}

		return $this->getLinkRenderer()->makeKnownLink( $title );
	}

	/**
	 * @see QueryPage::getPageHeader
	 *
	 * @return string
	 */
	public function getPageHeader() {
		$excludeNamespaces = array_diff(
			$this->namespaceInfo->getValidNamespaces(),
			$this->namespaceChecker->getWikibaseNamespaces()
		);

		$limit = $this->getRequest()->getIntOrNull( 'limit' );
		$ns = $this->getRequest()->getIntOrNull( 'namespace' );

		$titleInputHtml = '';
		$articlePath = $this->getConfig()->get( 'ArticlePath' );
		if ( strpos( $articlePath, '?' ) !== false ) {
			// Adopted from HTMLForm::getHiddenFields
			$titleInputHtml = Html::hidden( 'title', $this->getFullTitle()->getPrefixedText() ) . "\n";
		}

		return Html::openElement(
			'form',
			[
				'action' => $this->getPageTitle()->getLocalURL(),
			]
		) .
		$titleInputHtml .
		( $limit === null ? '' : Html::hidden( 'limit', $limit ) ) .
		Html::namespaceSelector( [
			'selected' => $ns === null ? '' : $ns,
			'all' => '',
			'exclude' => $excludeNamespaces,
			'label' => $this->msg( 'namespace' )->text(),
		] ) . ' ' .
		Html::submitButton(
			$this->msg( 'wikibase-unconnectedpages-submit' )->text(),
			[]
		) .
		Html::closeElement( 'form' );
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'maintenance';
	}

	/**
	 * @see QueryPage::linkParameters
	 *
	 * @return array
	 */
	public function linkParameters() {
		return [
			'namespace' => $this->getRequest()->getIntOrNull( 'namespace' ),
		];
	}

}
