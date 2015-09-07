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
 * Show a list of pages with a given badge.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialPagesWithBadges extends QueryPage {

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var string[]
	 */
	private $badgeIds;

	/**
	 * @var string
	 */
	private $siteId;

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
		$this->initServices(
			new LanguageFallbackLabelDescriptionLookupFactory(
				$wikibaseClient->getLanguageFallbackChainFactory(),
				$wikibaseClient->getTermLookup(),
				$wikibaseClient->getTermBuffer()
			),
			array_keys( $wikibaseClient->getSettings()->getSetting( 'badgeClassNames' ) ),
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param string[] $badgeIds
	 * @param string $siteId
	 */
	public function initServices(
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		array $badgeIds,
		$siteId
	) {
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->badgeIds = $badgeIds;
		$this->siteId = $siteId;
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

	/**
	 * @see QueryPage::getPageHeader
	 *
	 * @return string
	 */
	public function getPageHeader() {
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
		/** @var ItemId[] $badgeItemIds */
		$badgeItemIds = array_map(
			function( $badgeId ) {
				return new ItemId( $badgeId );
			},
			$this->badgeIds
		);

		$labelLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			$badgeItemIds
		);

		$html = '';

		foreach ( $this->badgeIds as $badgeId ) {
			$label = $labelLookup->getLabel( new ItemId( $badgeId ) );

			// show plain id if no label has been found
			$label = $label === null ? $badgeId : $label->getText();

			$html .= Html::element(
				'option',
				array(
					'value' => $badgeId,
					'selected' => $this->badgeId !== null && $this->badgeId->getSerialization() === $badgeId
				),
				$label
			);
		}

		return $html;
	}

	/**
	 * @see QueryPage::getQueryInfo
	 *
	 * @return array[]
	 */
	public function getQueryInfo() {
		return array(
			'tables' => array(
				'page',
				'page_props'
			),
			'fields' => array(
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
			),
			'conds' => array(
				'pp_propname' => 'wikibase-badge-' . $this->badgeId->getSerialization()
			),
			'options' => array(), // sorting is determined getOrderFields(), which returns array( 'value' ) per default.
			'join_conds' => array(
				'page_props' => array( 'JOIN', array( 'page_id = pp_page' ) )
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
	public function formatResult( $skin, $result ) {
		$title = Title::newFromID( $result->value );
		$out = Linker::linkKnown( $title );

		return $out;
	}

	/**
	 * @see QueryPage::isSyndicated
	 *
	 * @return bool
	 */
	public function isSyndicated() {
		return false;
	}

	/**
	 * @see QueryPage::isCacheable
	 *
	 * @return bool
	 */
	public function isCacheable() {
		return false;
	}

	/**
	 * @see QueryPage::linkParameters
	 *
	 * @return array
	 */
	public function linkParameters() {
		return array( 'badge' => $this->badgeId->getSerialization()  );
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
