<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\ChangeOp\ChangeOpSiteLink
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

	private function applySettings() {
		// Allow some badges for testing
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', array(
			'Q42' => '',
			'Q149' => '',
		) );
	}

	public function invalidConstructorProvider() {
		$this->applySettings();

		$argLists = array();

		$argLists[] = array( 'enwiki', 1234 );
		$argLists[] = array( 1234, 'Berlin' );
		$argLists[] = array( 'enwiki', 'Berlin', 'Nyan Certified' );
		$argLists[] = array( 'plwiki', 'Warszawa', array( 'FA', 'GA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new ItemId( 'Q42' ), 'FA' ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new PropertyId( 'P42' ) ) );
		$argLists[] = array( 'plwiki', 'Warszawa', array( new ItemId( 'Q3552127832535' ) ) );

		return $argLists;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, $badges = null ) {
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function changeOpSiteLinkProvider() {
		$this->applySettings();

		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSiteLink( $siteLink );
		}

		$args = array();

		// adding sitelink with badges
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) ),
			array_merge( $existingSiteLinks, array ( $enSiteLink ) )
		);

		// deleting sitelink
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'dewiki', null ),
			array( $plSiteLink )
		);

		// setting badges on existing sitelink
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		// changing sitelink without modifying badges
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Test', array( new ItemId( 'Q42' ) ) )
			)
		);

		// change badges without modifying title
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'Q149' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q149' ) ) )
			)
		);

		// add duplicate badges
		$args[] = array(
			$item->copy(),
			new ChangeOpSiteLink( 'plwiki', null, array( new ItemId( 'q42' ), new ItemId( 'Q149' ), new ItemId( 'Q42' ) ) ),
			array(
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 *
	 * @param Item $entity
	 * @param ChangeOpSiteLink $changeOpSiteLink
	 * @param SiteLink[] $expectedSiteLinks
	 */
	public function testApply( Item $entity, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ) {
		$changeOpSiteLink->apply( $entity );

		$this->assertEquals(
			$expectedSiteLinks,
			$entity->getSiteLinks()
		);
	}

	public function invalidChangeOpSiteLinkProvider() {
		$this->applySettings();

		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSiteLink( $siteLink );
		}

		$args = array();

		// cannot change badges of non-existing sitelink
		$args[] = array(
			$item->copy(),
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
