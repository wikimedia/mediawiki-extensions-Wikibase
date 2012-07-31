<?php
namespace Wikibase\Test;
use Wikibase\SiteLink;
use Wikibase\ItemDiff;
use Wikibase\Item;
use Wikibase\ItemObject;

/**
 * Tests for the Wikibase\EntityObject deriving classes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

class ItemDiffTest extends \MediaWikiTestCase {

	public function provideApplyData() {
		$tests = array();

		// #0: add label
		$a = ItemObject::newEmpty();
		$a->setLabel( 'en', 'Test' );

		$b = $a->copy();
		$b->setLabel( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #1: remove label
		$a = ItemObject::newEmpty();
		$a->setLabel( 'en', 'Test' );
		$a->setLabel( 'de', 'Test' );

		$b = $a->copy();
		$b->removeLabel( array( 'en' ) );

		$tests[] = array( $a, $b );

		// #2: change label
		$a = ItemObject::newEmpty();
		$a->setLabel( 'en', 'Test' );

		$b = $a->copy();
		$b->setLabel( 'en', 'Test!!!' );

		// #3: add description ------------------------------
		$a = ItemObject::newEmpty();
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #4: remove description
		$a = ItemObject::newEmpty();
		$a->setDescription( 'en', 'Test' );
		$a->setDescription( 'de', 'Test' );

		$b = $a->copy();
		$b->removeDescription( array( 'en' ) );

		$tests[] = array( $a, $b );

		// #5: change description
		$a = ItemObject::newEmpty();
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'en', 'Test!!!' );

		$tests[] = array( $a, $b );

		// #6: add alias ------------------------------
		$a = ItemObject::newEmpty();
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'en', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #7: add alias language
		$a = ItemObject::newEmpty();
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'de', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #8: remove alias
		$a = ItemObject::newEmpty();
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->removeAliases( 'en', array( 'Foo' ) );

		$tests[] = array( $a, $b );

		// #9: remove alias language
		$a = ItemObject::newEmpty();
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->removeAliases( 'en', array( 'Foo', 'Bar' ) );

		$tests[] = array( $a, $b );

		// #10: add link ------------------------------
		$a = ItemObject::newEmpty();
		$a->addSiteLink( SiteLink::newFromText( 'enwiki', 'Test' ) );

		$b = $a->copy();
		$b->addSiteLink( SiteLink::newFromText(  'dewiki', 'Test' ) );

		$tests[] = array( $a, $b );

		// #11: remove link
		$a = ItemObject::newEmpty();
		$a->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test' ), 'set' );
		$a->addSiteLink( SiteLink::newFromText(  'dewiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->removeSiteLink( 'enwiki' );

		$tests[] = array( $a, $b );

		// #12: change link
		$a = ItemObject::newEmpty();
		$a->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test!!!' ), 'set' );

		$tests[] = array( $a, $b );

		return $tests;
	}

	/**
	 *
	 * @dataProvider provideApplyData
	 */
	public function testApply( Item $a, Item $b ) {
		$diff = $a->getDiff( $b );
		$diff->apply( $a );

		$this->assertArrayEquals( $a->getLabels(), $b->getLabels() );
		$this->assertArrayEquals( $a->getDescriptions(), $b->getDescriptions() );
		$this->assertArrayEquals( $a->getAllAliases(), $b->getAllAliases() );
		$this->assertArrayEquals( SiteLink::siteLinksToArray( $a->getSiteLinks() ), SiteLink::siteLinksToArray( $b->getSiteLinks() ) );
	}

}
