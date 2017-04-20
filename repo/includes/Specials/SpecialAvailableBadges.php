<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\PrefetchingTermLookup;

/**
 * Page for listing all available badges.
 *
 * @license GPL-2.0+
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
	 * @param PrefetchingTermLookup $prefetchingTermLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param string[] $badgeItems
	 */
	public function __construct(
		PrefetchingTermLookup $prefetchingTermLookup,
		EntityTitleLookup $entityTitleLookup,
		array $badgeItems
	) {
		parent::__construct( 'AvailableBadges' );

		$this->prefetchingTermLookup = $prefetchingTermLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->badgeItems = $badgeItems;
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->getOutput()->addHTML( $this->makeAllBadgesHtml() );
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

		$this->prefetchingTermLookup->prefetchTerms( $itemIds );

		$html = Html::openElement( 'ol' );
		foreach ( $itemIds as $id ) {
			$html .= $this->makeBadgeHtml( $id, $this->badgeItems[$id->getSerialization()] );
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
	private function makeBadgeHtml( ItemId $badgeId, $badgeClass ) {
		$title = $this->entityTitleLookup->getTitleForId( $badgeId );
		$description = $this->prefetchingTermLookup->getDescription(
			$badgeId,
			$this->getLanguage()->getCode()
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
