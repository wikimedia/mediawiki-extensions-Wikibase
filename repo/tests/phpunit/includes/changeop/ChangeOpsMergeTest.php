<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpsMerge;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\ItemContent;

/**
 * @since 0.5
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpsMergeTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct(){
		$from = $this->getItemContent( 'Q111' );
		$to = $this->getItemContent( 'Q222' );
		$changeOps = new ChangeOpsMerge( $from, $to );
		$this->assertInstanceOf( '\Wikibase\ChangeOpsMerge', $changeOps );
	}

	public function getItemContent( $id, $data = array() ){
		$item = new Item( $data );
		$item->setId( new ItemId( $id ) );
		$itemContent = new ItemContent( $item );
		return $itemContent;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testCanApply( $fromData, $toData, $expectedFromData, $expectedToData ){
		$from = $this->getItemContent( 'Q111', $fromData );
		$to = $this->getItemContent( 'Q222', $toData );
		$changeOps = new ChangeOpsMerge( $from, $to );

		$this->assertInstanceOf( '\Wikibase\ChangeOpsMerge', $changeOps );
		$this->assertTrue( $from->getEntity()->equals( new Item( $fromData ) ), 'FromItem was not filled correctly' );
		$this->assertTrue( $to->getEntity()->equals( new Item( $toData ) ), 'ToItem was not filled correctly' );

		$changeOps->apply();

		//Cycle through thr old claims and set the guids to null (we no longer know what they should be)
		$fromClaims = new Claims( $from->getEntity()->getClaims() );
		foreach( $from->getEntity()->getClaims() as $claim ) {
			$this->assertStringStartsWith(
				$from->getEntity()->getId()->getSerialization(), $claim->getGuid(),
				'FromItem has a claim prefixed with the wrong guid'
			);
			$fromClaims->removeClaim( $claim );
			$claim->setGuid( null );
			$fromClaims->addClaim( $claim );
		}
		$from->getEntity()->setClaims( $fromClaims );
		$toClaims = new Claims( $to->getEntity()->getClaims() );
		foreach( $to->getEntity()->getClaims() as $claim ) {
			$this->assertStringStartsWith( $to->getEntity()->getId()->getSerialization(), $claim->getGuid() );
			$toClaims->removeClaim( $claim );
			$claim->setGuid( null );
			$toClaims->addClaim( $claim );
		}
		$to->getEntity()->setClaims( $toClaims );

		$this->assertTrue( $from->getEntity()->equals( new Item( $expectedFromData ) ) );
		$this->assertTrue( $to->getEntity()->equals( new Item( $expectedToData ) ) );
	}

	public static function provideData(){
		return array(
			//check all elements move individually
			array(
				array( 'label' => array( 'en' => 'foo' ) ),
				array(),
				array(),
				array( 'label' => array( 'en' => 'foo' ) ),
			),
			array(
				array( 'description' => array( 'en' => 'foo' ) ),
				array(),
				array(),
				array( 'description' => array( 'en' => 'foo' ) ),
			),
			array(
				array( 'aliases' => array( 'en' => array( 'foo', 'bar' ) ) ),
				array(),
				array(),
				array( 'aliases' => array( 'en' =>  array( 'foo', 'bar' ) ) ),
			),
			array(
				array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
				array(),
				array(),
				array( 'links' => array( 'enwiki' => array( 'name' => 'foo', 'badges' => array() ) ) ),
			),
			array(
				array( 'claims' => array(
					array(
						'm' => array( 'novalue', 56 ),
						'q' => array( ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF13-A390BCD9C556' )
				),
				),
				array(),
				array(),
				array( 'claims' => array(
					array(
						'm' => array( 'novalue', 56 ),
						'q' => array( ),
						'g' => null )
				),
				),
			),
			array(
				array( 'claims' => array(
					array(
						'm' => array( 'novalue', 56 ),
						'q' => array( array(  'novalue', 56  ) ),
						'g' => 'Q111$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' )
				),
				),
				array(),
				array(),
				array( 'claims' => array(
					array(
						'm' => array( 'novalue', 56 ),
						'q' => array( array(  'novalue', 56  ) ),
						'g' => null )
				),
				),
			),
			array(
				array(
					'label' => array( 'en' => 'foo', 'pt' => 'ptfoo' ),
					'description' => array( 'en' => 'foo', 'pl' => 'pldesc'  ),
					'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
					'links' => array( 'dewiki' => array( 'name' => 'foo', 'badges' => array() ) ),
					'claims' => array(
						array(
							'm' => array( 'novalue', 88 ),
							'q' => array( array(  'novalue', 88  ) ),
							'g' => 'Q111$D8404CDA-25E4-4334-AF88-A3290BCD9C0F' )
					),
				),
				array(),
				array(),
				array(
					'label' => array( 'en' => 'foo', 'pt' => 'ptfoo'  ),
					'description' => array( 'en' => 'foo', 'pl' => 'pldesc' ),
					'aliases' => array( 'en' => array( 'foo', 'bar' ), 'de' => array( 'defoo', 'debar' ) ),
					'links' => array( 'dewiki' => array( 'name' => 'foo', 'badges' => array() ) ),
					'claims' => array(
						array(
							'm' => array( 'novalue', 88 ),
							'q' => array( array(  'novalue', 88  ) ),
							'g' => null )
					),
				),
			),
		);
	}

}