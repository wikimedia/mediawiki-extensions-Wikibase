<?php
/**
 *  Tests for the WikibaseItemDiff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class WikibaseItemDiffTests extends MediaWikiTestCase {

	/**
	 * @dataProvider provideForGreatJustice
	 *
	 * @param WikibaseItem $oldItem
	 * @param WikibaseItem $newItem
	 */
	public function testAllOfTheMethods( WikibaseItem $oldItem, WikibaseItem $newItem, WikibaseMapDiff $siteLinkDiff, WikibaseListDiff $aliasesDiff ) {

	}


	/**
	 * @return array
	 */
	public function provideForGreatJustice() {
		$item = WikibaseItem::newEmpty();

		$item->setLabel( 'en', 'Berlin' );
		$item->setDescription( 'en', 'The capital of Germany' );
		$item->addAliases( 'en', array( 'Berlin (City)', 'Berlin (Germany)' ) );

		$item->setLabel( 'nl', 'Berlijn' );
		$item->setDescription( 'nl', 'De hoofdstad van Duidsland' );

		$item->addSiteLink( 'en', 'Berlin' );
		$item->addSiteLink( 'nl', 'Berlijn' );

		return array(
			array(
				WikibaseItem::newEmpty(),
				WikibaseItem::newEmpty(),
				WikibaseMapDiff::newEmpty(),
			),
			array(
				$item,
				$item,
				WikibaseMapDiff::newEmpty(),
			),
			array(
				$item,
				WikibaseItem::newEmpty(),
				WikibaseMapDiff::newFromArrays( $item->getSiteLinks(), array() ),
			),
			array(
				WikibaseItem::newEmpty(),
				$item,
				WikibaseMapDiff::newFromArrays( array(), $item->getSiteLinks() )
			),
		);
	}

}