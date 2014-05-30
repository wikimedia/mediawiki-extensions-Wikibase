<?php

namespace Wikibase\Test;

use Wikibase\Change;
use Wikibase\ChangeRow;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\DiffChange;
use Wikibase\Entity;
use Wikibase\EntityChange;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;

/**
 * Test change data for ChangeRowTest
 *
 * @since 0.2
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
final class TestChanges {

	protected static function getItem() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'Venezuela' );
		$item->setDescription( 'en', 'a country' );
		$item->addAliases( 'en', array( 'Bolivarian Republic of Venezuela' ) );

		$item->addSiteLink( new SimpleSiteLink( 'enwiki', 'Venezuela' )  );
		$item->addSiteLink( new SimpleSiteLink( 'jawiki', 'ベネズエラ' )  );
		$item->addSiteLink( new SimpleSiteLink( 'cawiki', 'Veneçuela' )  );

		return $item;
	}

	public static function getChange() {
		$changes = self::getInstances();

		return $changes['set-de-label']->toArray();
	}

	protected static function getInstances() {
		static $changes = array();

		if ( empty( $changes ) ) {
			$empty = Property::newEmpty();
			$empty->setId( new PropertyId( 'p100' ) );

			$changes['property-creation'] = EntityChange::newFromUpdate( EntityChange::ADD, null, $empty );
			$changes['property-deletion'] = EntityChange::newFromUpdate( EntityChange::REMOVE, $empty, null );

			// -----
			$old = Property::newEmpty();
			$old->setId( new PropertyId( 'p100' ) );
			$new = $old->copy();

			$new->setLabel( "de", "dummy" );
			$changes['property-set-label'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );

			// -----
			$old = Item::newEmpty();
			$old->setId( new ItemId( 'q100' ) );

			/* @var Item $new */
			$new = $old->copy();

			$changes['item-creation'] = EntityChange::newFromUpdate( EntityChange::ADD, null, $new );
			$changes['item-deletion'] = EntityChange::newFromUpdate( EntityChange::REMOVE, $old, null );

			// -----

			//FIXME: EntityChange::newFromUpdate causes Item::getSiteLinks to be called,
			//       which uses SiteLink::newFromText, which in turn uses the Sites singleton
			//       which relies on the database. This is inconsistent with the Site objects
			//       generated here, or elsewhere in test cases.

			$link = new SimpleSiteLink( 'dewiki', "Dummy" );
			$new->addSiteLink( $link, 'add' );
			$changes['set-dewiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$link = new SimpleSiteLink( 'enwiki', "Emmy" );
			$new->addSiteLink( $link, 'add' );
			$changes['set-enwiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// -----
			$new->removeSiteLink( 'enwiki' );
			$new->removeSiteLink( 'dewiki' );

			$link = new SimpleSiteLink( 'enwiki', "Emmy" );
			$new->addSiteLink( $link, 'add' );

			$link = new SimpleSiteLink( 'dewiki', "Dummy" );
			$new->addSiteLink( $link, 'add' );

			$changes['change-sitelink-order'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// -----
			$link = new SimpleSiteLink( 'dewiki', "Dummy2" );
			$new->addSiteLink( $link, 'set' );
			$changes['change-dewiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$link = new SimpleSiteLink( 'enwiki', "Emmy2" );
			$new->addSiteLink( $link, 'set' );
			$changes['change-enwiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$link = new SimpleSiteLink( 'enwiki', "Emmy2", array( new ItemId( 'Q17' ) ) );
			$new->addSiteLink( $link, 'set' );
			$changes['change-enwiki-sitelink-badges'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$new->removeSiteLink( 'dewiki', false );
			$changes['remove-dewiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// -----
			$new->setLabel( "de", "dummy" );
			$changes['set-de-label'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$new->setLabel( "en", "emmy" );
			$changes['set-en-label'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$new->setAliases( "en", array( "foo", "bar" ) );
			$changes['set-en-aliases'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// -----
			$propertyId = new PropertyId( 'p23' );
			$snak = new PropertyNoValueSnak( $propertyId );
			$claim = new Claim( $snak );
			$claim->setGuid( 'TEST$test-guid' );

			$claims = new Claims( array( $claim ) );
			$new->setClaims( $claims );
			$changes['add-claim'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			$claims = new Claims();
			$new->setClaims( $claims );
			$changes['remove-claim'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// -----
			$changes['item-deletion-linked'] = EntityChange::newFromUpdate( EntityChange::REMOVE, $old, null );

			// -----
			$new->removeSiteLink( 'enwiki', false );
			$changes['remove-enwiki-sitelink'] = EntityChange::newFromUpdate( EntityChange::UPDATE, $old, $new );
			$old = $new->copy();

			// apply all the defaults ----------
			$defaults = array(
				'user_id' => 0,
				'time' => '20130101000000',
				'type' => 'test',
			);

			$rev = 1000;

			/* @var EntityChange $change */
			foreach ( $changes as $key => $change ) {
				$change->setComment( "$key:1|" );

				$meta = array(
					'comment' => '',
					'page_id' => 23,
					'bot' => false,
					'rev_id' => $rev,
					'parent_id' => $rev -1,
					'user_text' => 'Some User',
					'time' => wfTimestamp( TS_MW ),
				);

				$change->setMetadata( $meta );
				self::applyDefaults( $change, $defaults );

				$rev += 1;
			}
		}

		$clones = array();

		/* @var EntityChange $change */
		foreach ( $changes as $key => $change ) {
			$clones[$key] = clone $change;
		}

		return $clones;
	}

	/**
	 * Returns a list of Change objects for testing. Instances are not cloned.
	 *
	 * @param string[]|null $changeFilter The changes to include, as a list if change names
	 *
	 * @param string[]|null $infoFilter The info to include in each change, as
	 *        a list of keys to the info array.
	 *
	 * @return ChangeRow[] a list of changes
	 */
	public static function getChanges( $changeFilter = null, $infoFilter = null ) {
		$changes = self::getInstances();

		// filter changes by key
		if ( $changeFilter!== null ) {
			$changes = array_intersect_key( $changes, array_flip( $changeFilter ) );
		}

		// filter info field by key
		if ( $infoFilter !== null ) {
			$infoFilter = array_flip( $infoFilter );
			$filteredChanges = array();

			/* @var ChangeRow $change */
			foreach ( $changes as $change ) {
				if ( $change->hasField( 'info' ) ) {
					$info = $change->getField( 'info' );

					$info = array_intersect_key( $info, $infoFilter );

					$change->setField( 'info', $info );
				}

				$filteredChanges[] = $change;
			}

			$changes = $filteredChanges;
		}

		return $changes;
	}

	protected static function applyDefaults( Change $change, array $defaults ) {
		foreach ( $defaults as $name => $value ) {
			if ( !$change->hasField( $name ) ) {
				$change->setField( $name, $value );
			}
		}
	}

	public static function getDiffs() {
		$changes = self::getChanges();
		$diffs = array();

		foreach ( $changes as $change ) {
			if ( $change instanceof DiffChange
				&& $change->hasDiff() ) {
				$diffs[] = $change->getDiff();
			}
		}

		return $diffs;
	}

	public static function getEntities() {
		$entityList = array();

		$entities = array(
			Item::newEmpty(),
			Property::newEmpty(),
		);

		/**
		 * @var Entity $entity
		 */
		foreach( $entities as $entity ) {
			$entityList[] = $entity;

			$entity->setId( 112 );
			$entity->setLabel( 'ja', '\u30d3\u30fc\u30eb' );

			$entityList[] = $entity;
		}


		return $entityList;
	}
}
