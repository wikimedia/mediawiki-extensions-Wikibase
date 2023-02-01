<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Page for listing all available badges.
 *
 * @license GPL-2.0-or-later
 * @author Victor Barbu < victorbarbu08@gmail.com >
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialAvailableBadges extends SpecialWikibasePage {

	/**
	 * @var PrefetchingTermLookup
	 */
	private $prefetchingTermLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup,
		SettingsArray $repoSettings
	) {
		parent::__construct( 'AvailableBadges' );

		$this->prefetchingTermLookup = $prefetchingTermLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->badgeItems = $repoSettings->getSetting( 'badgeItems' );
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$outputPage = $this->getOutput();
		$outputPage->addHTML( $this->makeAllBadgesHtml() );
		$outputPage->addModuleStyles( 'wikibase.alltargets' );
	}

	/**
	 * @return string HTML
	 */
	private function makeAllBadgesHtml() {
		if ( empty( $this->badgeItems ) ) {
			return Html::element(
				'p',
				[],
				$this->msg( 'wikibase-availablebadges-emptylist' )->text()
			);
		}

		/** @var ItemId[] $itemIds */
		$itemIds = array_map( function( $idString ) {
			// XXX: Maybe we should use PrefixMappingEntityIdParser for federation?
			return new ItemId( $idString );
		}, array_keys( $this->badgeItems ) );

		$languageCode = $this->getLanguage()->getCode();
		$languageChain = $this->languageFallbackChainFactory->newFromLanguageCode( $languageCode )->getFetchLanguageCodes();
		$this->prefetchingTermLookup->prefetchTerms( $itemIds, [ 'description', 'label' ], $languageChain );

		$html = Html::openElement( 'ol' );
		foreach ( $itemIds as $id ) {
			$html .= $this->makeBadgeHtml( $id, $this->badgeItems[$id->getSerialization()], $languageCode );
		}
		$html .= Html::closeElement( 'ol' );

		return $html;
	}

	/**
	 * @param ItemId $badgeId
	 * @param string $badgeClass
	 *
	 * @return string HTML
	 */
	private function makeBadgeHtml( ItemId $badgeId, string $badgeClass, string $languageCode ) {
		$title = $this->entityTitleLookup->getTitleForId( $badgeId );
		$description = $this->prefetchingTermLookup->getDescription(
			$badgeId,
			$languageCode
		);

		$html = Html::openElement( 'li' );
		$html .= Html::element( 'span', [ 'class' => 'wb-badge ' . $badgeClass ] );
		$html .= $this->getLinkRenderer()->makeLink( $title );
		if ( $description !== null ) {
			$html .= $this->msg( 'comma-separator' )->escaped() . htmlspecialchars( $description );
		}
		$html .= Html::closeElement( 'li' );

		return $html;
	}

}
