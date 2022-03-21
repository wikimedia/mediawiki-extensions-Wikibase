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
use Wikibase\Lib\SettingsArray;
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

	/** @var int */
	private $unconnectedPagePagePropMigrationStage;

	public function __construct(
		NamespaceInfo $namespaceInfo,
		TitleFactory $titleFactory,
		ClientDomainDbFactory $db,
		NamespaceChecker $namespaceChecker,
		SettingsArray $settings
	) {
		parent::__construct( 'UnconnectedPages' );
		$this->namespaceInfo = $namespaceInfo;
		$this->titleFactory = $titleFactory;
		$this->namespaceChecker = $namespaceChecker;
		$this->db = $db->newLocalDb();
		$this->setDBLoadBalancer( $this->db->loadBalancer() );
		$this->unconnectedPagePagePropMigrationStage = $settings->getSetting( 'tmpUnconnectedPagePagePropMigrationStage' );
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

		if ( $this->unconnectedPagePagePropMigrationStage >= MIGRATION_NEW ) {
			if ( $ns !== null && in_array( $ns, $wbNamespaces ) ) {
				$conds['pp_sortkey'] = $ns;
			} else {
				$conds['pp_sortkey'] = $wbNamespaces;
			}
		} else {
			// b/c: We can't yet use the new "unexpectedUnconnectedPage" page property.
			if ( $ns !== null && in_array( $ns, $wbNamespaces ) ) {
				$conds['page_namespace'] = $ns;
			} else {
				$conds['page_namespace'] = $wbNamespaces;
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
		$conds = $this->buildNamespaceConditionals();

		$joinConds = [
			'page_props' => [
				'INNER JOIN',
				[ 'page_id = pp_page', 'pp_propname' => 'unexpectedUnconnectedPage' ]
			],
		];

		if ( $this->unconnectedPagePagePropMigrationStage < MIGRATION_NEW ) {
			// b/c: We can't yet use the new "unexpectedUnconnectedPage" page property.
			$conds['page_is_redirect'] = 0;
			$conds['pp_propname'] = null;
			$joinConds = [
				'page_props' => [
					'LEFT JOIN',
					[ 'page_id = pp_page', 'pp_propname' => [ 'wikibase_item', 'expectedUnconnectedPage' ] ]
				],
			];
		}

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
			'conds' => $conds,
			// Sorting is determined getOrderFields()
			'options' => [],
			'join_conds' => $joinConds,
		];
	}

	/**
	 * @return string[]
	 */
	protected function getOrderFields() {
		if ( $this->unconnectedPagePagePropMigrationStage < MIGRATION_NEW ) {
			// b/c: With the old page prop we can't use pp_sortkey
			return parent::getOrderFields();
		}
		// Should make use of the "pp_propname_sortkey_page" index.
		return [ 'pp_sortkey', 'page_id' ];
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
				'action' => $this->getPageTitle()->getLocalURL()
			]
		) .
		$titleInputHtml .
		( $limit === null ? '' : Html::hidden( 'limit', $limit ) ) .
		Html::namespaceSelector( [
			'selected' => $ns === null ? '' : $ns,
			'all' => '',
			'exclude' => $excludeNamespaces,
			'label' => $this->msg( 'namespace' )->text()
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
