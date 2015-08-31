<?php


namespace Wikibase\Client\Specials;

use Html;
use InvalidArgumentException;
use Linker;
use QueryPage;
use Skin;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * Show pages with a given badge.
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialPagesWithBadges extends QueryPage {

	/**
	 * @var string[]
	 */
	private $badgeIds;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var ItemId|null
	 */
	private $badgeId;

	/**
	 * @see SpecialPage::__construct
	 *
	 * @param string $name
	 */
	public function __construct( $name = 'PagesWithBadges' ) {
		parent::__construct( $name );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$this->setServices(
			array_keys( $wikibaseClient->getSettings()->getSetting( 'badgeClassNames' ) ),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			new LanguageFallbackLabelDescriptionLookupFactory(
				$wikibaseClient->getLanguageFallbackChainFactory(),
				$wikibaseClient->getStore()->getTermIndex()
			)
		);
	}

	public function setServices(
		array $badgeIds,
		$siteId,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	) {
		$this->badgeIds = $badgeIds;
		$this->siteId = $siteId;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
	}

	/**
	 * @see QueryPage::execute
	 *
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		$this->prepareParams( $subPage );

		if ( $this->badgeId !== null ) {
			parent::execute( $subPage );
		} else {
			$this->setHeaders();
			$this->outputHeader();
			$this->getOutput()->addHTML( $this->getPageHeader() );
		}
	}

	private function prepareParams( $subPage ) {
		$badge = $this->getRequest()->getText( 'badge', $subPage );

		try {
			$this->badgeId = new ItemId( $badge );
		} catch ( InvalidArgumentException $ex ) {
			if ( $badge ) {
				$this->getOutput()->addHTML(
					Html::element(
						'p',
						array(
							'class' => 'error'
						),
						$this->msg( 'wikibase-pageswithbadges-invalid-id', $badge )
					)
				);
			}
		}
	}

	function getPageHeader() {
		return Html::openElement(
			'form',
			array(
				'action' => $this->getPageTitle()->getLocalURL()
			)
		) .
		Html::openElement( 'fieldset' ) .
		Html::element(
			'legend',
			array(),
			$this->msg( 'wikibase-pageswithbadges-legend' )->text()
		) .
		Html::openElement( 'p' ) .
		Html::element(
			'label',
			array(
				'for' => 'wb-pageswithbadges-badge'
			),
			$this->msg( 'wikibase-pageswithbadges-badge' )->text()
		) . ' ' .
		Html::rawElement(
			'select',
			array(
				'name' => 'badge',
				'id' => 'wb-pageswithbadges-badge',
				'class' => 'wb-select'
			),
			$this->getOptionsHtml()
		) . ' ' .
		Html::input(
			'',
			$this->msg( 'wikibase-pageswithbadges-submit' )->text(),
			'submit',
			array(
				'id' => 'wikibase-pageswithbadges-submit',
				'class' => 'wb-input-button'
			)
		) .
		Html::closeElement( 'p' ) .
		Html::closeElement( 'fieldset' ) .
        Html::closeElement( 'form' );
	}

	private function getOptionsHtml() {
		$html = '';

		foreach ( $this->badgeIds as $badgeId ) {
			$html .= Html::element(
				'option',
				array(
					'value' => $badgeId,
					'selected' => $this->badgeId !== null && $this->badgeId->getSerialization() === $badgeId
				),
				$badgeId // TODO show label
			);
		}

		return $html;
	}

	/**
	 * @see QueryPage::getQueryInfo
	 *
	 * @return array[]
	 */
	function getQueryInfo() {
		return array(
			'tables' => array(
				'page',
				'page_props',
				'wb_items_per_site',
				'wb_badges_per_sitelink',
			),
			'fields' => array(
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
			),
			'conds' => array(
				'bps_site_id' => $this->siteId,
				'bps_badge_id' => $this->badgeId->getSerialization()
			),
			'options' => array(), // sorting is determined getOrderFields(), which returns array( 'value' ) per default.
			'join_conds' => array(
				'page_props' => array( 'JOIN', array( 'page_id = pp_page' ) ),
				'wb_items_per_site' => array( 'JOIN', array( "pp_value = CONCAT( 'Q', ips_item_id )", "pp_propname = 'wikibase_item'" ) ),
				'wb_badges_per_sitelink' => array( 'JOIN', array( 'ips_site_page = bps_site_page' ) )
			)
		);
	}

	/**
	 * @see QueryPage::formatResult
	 *
	 * @param Skin $skin
	 * @param object $result
	 *
	 * @return string
	 */
	function formatResult( $skin, $result ) {
		$title = Title::newFromID( $result->value );
		$out = Linker::linkKnown( $title );

		return $out;
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'pages';
	}

}
