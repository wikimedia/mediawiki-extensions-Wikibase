<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use Linker;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for displaying diagnostics about the dispatch process.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Victor Barbu < victorbarbu08@gmail.com >
 */
class SpecialAvailableBadges extends SpecialWikibasePage {

	/**
	 * @var WikibaseRepo
	 */
	private $repo;

	/**
	 * @var \Wikibase\Store\BufferingTermLookup
	 */
	private $bufferingTermLookup;

	public function __construct() {
		parent::__construct( 'AvailableBadges' );

		$this->repo = WikibaseRepo::getDefaultInstance();
		$this->bufferingTermLookup = $this->repo->getBufferingTermLookup();
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->displayResult();
	}

	private function displayResult() {
		$out = $this->getOutput();
		$badgeItems = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'badgeItems' );
		$itemIdParser = new ItemIdParser();

		$itemIds = array_map( function( $item ) use ( $itemIdParser ) {
			return $itemIdParser->parse( $item );
		}, array_keys( $badgeItems ) );

		$this->bufferingTermLookup->prefetchTerms( $itemIds );

		$out->addHTML( Html::openElement( 'ol' ) );
		foreach ( $itemIds as $item ) {
			$this->displayRow( $item, $badgeItems[$item->getSerialization()] );
		}
		$out->addHTML( Html::closeElement( 'ol' ) );
	}

	/**
	 * Render one badge.
	 *
	 * @param \Wikibase\DataModel\Entity\ItemId $item Item ID to render
	 * @param string $badgeClass The given badge class
	 */
	private function displayRow( ItemId $item, $badgeClass ) {
		$out = $this->getOutput();
		$entityTitleLookup = $this->repo->getEntityTitleLookup();

		$title = $entityTitleLookup->getTitleForId( $item );
		$description = $this->bufferingTermLookup->getDescription(
			$item,
			$this->getLanguage()->getCode()
		);

		$out->addHTML( Html::openElement( 'li' ) );
		$out->addHTML( Html::element( 'span', [
			'class' => 'wb-badge ' . $badgeClass,
		] ) );
		$out->addHTML( Linker::link( $title ) );
		$out->addHTML( ' - ' . $description );
		$out->addHTML( Html::closeElement( 'li' ) );
	}

}
