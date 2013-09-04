<?php

namespace Wikibase\Test;

use Site;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpSiteLink
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpSiteLinkTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, $badges = null ) {
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function invalidConstructorProvider() {
		$argLists = array();

		$argLists[] = array( 'enwiki', 1234 );
		$argLists[] = array( 1234, 'Berlin' );
		$argLists[] = array( 'enwiki', 'Berlin', 'Nyan Certified' );
		$argLists[] = array( 'plwiki', 'Warszawa', array( 'FA', 'GA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new ItemId( 'Q42' ), 'FA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new PropertyId( 'P42' ) ) );

		return $argLists;
	}

	public function changeOpSiteLinkProvider() {
		$deSiteLink = new SimpleSiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SimpleSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) );
		$plSiteLink = new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSimpleSiteLink( $siteLink );
		}

		$args = array();

		// adding sitelink with badges
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) ),
			array_merge( $existingSiteLinks, array ( $enSiteLink ) )
		);

		// deleting sitelink
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'dewiki' ),
			array( $plSiteLink )
		);

		// setting badges on existing sitelink
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		// changing sitelink without modifying badges
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Test', array( new ItemId( 'Q42' ) ) )
			)
		);

		// change badges without modifying title
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q149' ) ) )
			)
		);

		// add duplicate badges
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'q42' ), new ItemId( 'Q149' ), new ItemId( 'Q42' ) ) ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 *
	 * @param Item $entity
	 * @param ChangeOpSiteLink $changeOpSiteLink
	 * @param SimpleSiteLink[] $expectedSiteLinks
	 */
	public function testApply( Item $entity, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ) {
		$changeOpSiteLink->apply( $entity );

		$this->assertEquals(
			$expectedSiteLinks,
			$entity->getSimpleSiteLinks()
		);
	}

	public function invalidChangeOpSiteLinkProvider() {
		$deSiteLink = new SimpleSiteLink( 'dewiki', 'Berlin' );
		$plSiteLink = new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSimpleSiteLink( $siteLink );
		}

		$args = array();

		// cannot change badges of non-existing sitelink
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'enwiki', null, array( new ItemId( 'Q149' ) ) ),
		);

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpSiteLinkProvider
	 *
	 * @param Item $entity
	 * @param ChangeOpSiteLink $changeOpSiteLink
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testApplyWithInvalidData( Item $entity, ChangeOpSiteLink $changeOpSiteLink ) {
		$changeOpSiteLink->apply( $entity );
	}

}
