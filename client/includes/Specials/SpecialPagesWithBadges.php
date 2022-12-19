<?php

namespace Wikibase\Client\Specials;

use Html;
use HTMLForm;
use InvalidArgumentException;
use QueryPage;
use Skin;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * Show a list of pages with a given badge.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialPagesWithBadges extends QueryPage {

	/**
	 * @var FallbackLabelDescriptionLookupFactory
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
	 * @param FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param string[] $badgeIds
	 * @param string $siteId
	 */
	public function __construct(
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		array $badgeIds,
		$siteId
	) {
		parent::__construct( 'PagesWithBadges' );

		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->badgeIds = $badgeIds;
		$this->siteId = $siteId;
	}

	public static function factory(
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		SettingsArray $clientSettings
	): self {
		return new self(
			$labelDescriptionLookupFactory,
			array_keys( $clientSettings->getSetting( 'badgeClassNames' ) ),
			$clientSettings->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * @see QueryPage::execute
	 *
	 * @param string|null $subPage
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
		$badge = $this->getRequest()->getText( 'badge', $subPage ?: '' );

		try {
			$this->badgeId = new ItemId( $badge );
		} catch ( InvalidArgumentException $ex ) {
			if ( $badge ) {
				$this->getOutput()->addHTML(
					Html::element(
						'p',
						[
							'class' => 'error',
						],
						$this->msg( 'wikibase-pageswithbadges-invalid-id', $badge )->text()
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
		$formDescriptor = [
			'badge' => [
				'name' => 'badge',
				'type' => 'select',
				'id' => 'wb-pageswithbadges-badge',
				'label-message' => 'wikibase-pageswithbadges-badge',
				'options' => $this->getOptionsArray(),
			],
			'submit' => [
				'name' => '',
				'type' => 'submit',
				'id' => 'wikibase-pageswithbadges-submit',
				'default' => $this->msg( 'wikibase-pageswithbadges-submit' )->text(),
			],
		];

		if ( $this->badgeId !== null ) {
			$formDescriptor['badge']['default'] = $this->badgeId->getSerialization();
		}

		return HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'wikibase-pageswithbadges-legend' )
			->suppressDefaultSubmit()
			->prepareForm()
			->getHTML( '' );
	}

	private function getOptionsArray() {
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

		$options = [];

		foreach ( $this->badgeIds as $badgeId ) {
			$label = $labelLookup->getLabel( new ItemId( $badgeId ) );

			// show plain id if no label has been found
			$label = $label === null ? $badgeId : $label->getText();

			$options[$label] = $badgeId;
		}

		return $options;
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
			'conds' => [
				'pp_propname' => 'wikibase-badge-' . $this->badgeId->getSerialization(),
			],
			'options' => [], // sorting is determined getOrderFields(), which returns [ 'value' ] per default.
			'join_conds' => [
				'page_props' => [ 'JOIN', [ 'page_id = pp_page' ] ],
			],
		];
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
		// FIXME: This should use a TitleFactory.
		$title = Title::newFromID( $result->value );
		$out = $this->getLinkRenderer()->makeKnownLink( $title );

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
		return [ 'badge' => $this->badgeId->getSerialization() ];
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
