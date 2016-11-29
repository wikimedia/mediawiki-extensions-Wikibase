<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
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

		$this->displayResult();
	}

	private function displayResult() {
		$out = $this->getOutput();
		// XXX: Maybe we should use PrefixMappingEntityIdParser for federation?
		$itemIdParser = new ItemIdParser();

		$itemIds = array_map( function( $item ) use ( $itemIdParser ) {
			return $itemIdParser->parse( $item );
		}, array_keys( $this->badgeItems ) );

		if ( empty( $itemIds ) ) {
			$out->addHTML( Html::element(
				'p',
				[],
				$this->msg( 'wikibase-availablebadges-emptylist' )->text()
			) );

			return;
		}

		$this->prefetchingTermLookup->prefetchTerms( $itemIds );

		$out->addHTML( Html::openElement( 'ol' ) );
		foreach ( $itemIds as $item ) {
			$this->displayRow( $item, $this->badgeItems[$item->getSerialization()] );
		}
		$out->addHTML( Html::closeElement( 'ol' ) );
	}

	/**
	 * Render one badge.
	 *
	 * @param ItemId $item Item ID to render
	 * @param string $badgeClass The given badge class
	 */
	private function displayRow( ItemId $item, $badgeClass ) {
		$out = $this->getOutput();

		$title = $this->entityTitleLookup->getTitleForId( $item );
		$description = $this->prefetchingTermLookup->getDescription(
			$item,
			$this->getLanguage()->getCode()
		);

		$out->addHTML( Html::openElement( 'li' ) );
		$out->addHTML( Html::element( 'span', [
			'class' => 'wb-badge ' . $badgeClass,
		] ) );
		$out->addHTML( $this->getLinkRenderer()->makeLink( $title ) );
		if ( $description !== null ) {
			$out->addHTML( ' - ' . $description );
		}
		$out->addHTML( Html::closeElement( 'li' ) );
	}

}
