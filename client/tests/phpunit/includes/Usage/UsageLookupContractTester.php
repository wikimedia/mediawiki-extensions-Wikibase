<?php

namespace Wikibase\Client\Tests\Usage;

use InvalidArgumentException;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Helper class for testing UsageLookup implementations,
 * providing generic tests for the interface's contract.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UsageLookupContractTester {

	/**
	 * @var UsageLookup
	 */
	private $lookup;

	/**
	 * @var callable function( $pageId, EntityUsage[] $usages, $timestamp )
	 */
	private $putUsagesCallback;

	/**
	 * @param UsageLookup $lookup The lookup under test
	 * @param callable $putUsagesCallback function( $pageId, EntityUsage[] $usages, $timestamp )
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( UsageLookup $lookup, $putUsagesCallback ) {
		if ( !is_callable( $putUsagesCallback ) ) {
			throw new InvalidArgumentException( '$putUsagesCallback must be callable' );
		}

		$this->lookup = $lookup;
		$this->putUsagesCallback = $putUsagesCallback;
	}

	private function putUsages( $pageId, array $usages ) {
		call_user_func( $this->putUsagesCallback, $pageId, $usages );
	}

	public function testGetUsageForPage() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' );

		$usages = array( $u3i, $u3l, $u4l );

		$this->putUsages( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUsagesForPage( 24 ) );

		$actualUsage = $this->lookup->getUsagesForPage( 23 );
		Assert::assertCount( 3, $actualUsage );

		$actualUsageStrings = $this->getUsageStrings( $actualUsage );
		$expectedUsageStrings = $this->getUsageStrings( $usages );
		Assert::assertEquals( $expectedUsageStrings, $actualUsageStrings );

		$this->putUsages( 23, array() );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3s = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' );
		$u4t = new EntityUsage( $q4, EntityUsage::TITLE_USAGE );

		$this->putUsages( 23, array( $u3s, $u3l, $u4l ) );
		$this->putUsages( 42, array( $u4l, $u4t ) );

		$pages = $this->lookup->getPagesUsing( array( $q6 ) );
		Assert::assertEmpty( iterator_to_array( $pages ) );

		$pages = $this->lookup->getPagesUsing( array( $q3 ) );
		$this->assertSamePageEntityUsages(
			array( 23 => new PageEntityUsages( 23, array( $u3s, $u3l ) ) ),
			iterator_to_array( $pages ),
			'Pages using Q3'
		);

		$pages = $this->lookup->getPagesUsing(
			array( $q4, $q3 ),
			array( EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE, 'de' ) )
		);
		$this->assertSamePageEntityUsages(
			array(
				23 => new PageEntityUsages( 23, array( $u3l, $u4l ) ),
				42 => new PageEntityUsages( 42, array( $u4l ) ),
			),
			iterator_to_array( $pages ),
			'Pages using "label" on Q4 or Q3'
		);

		$pages = $this->lookup->getPagesUsing( array( $q3 ), array( EntityUsage::ALL_USAGE ) );
		Assert::assertEmpty( iterator_to_array( $pages ), 'Pages using "all" on Q3' );

		$pages = $this->lookup->getPagesUsing( array( $q4 ), array( EntityUsage::SITELINK_USAGE ) );
		Assert::assertEmpty( iterator_to_array( $pages ), 'Pages using "sitelinks" on Q4' );

		$pages = $this->lookup->getPagesUsing(
			array( $q3, $q4 ),
			array( EntityUsage::TITLE_USAGE, EntityUsage::SITELINK_USAGE )
		);
		Assert::assertCount(
			2,
			iterator_to_array( $pages ),
			'Pages using "title" or "sitelinks" on Q3 or Q4'
		);

		$this->putUsages( 23, array() );
	}

	/**
	 * @param PageEntityUsages[] $expected
	 * @param PageEntityUsages[] $actual
	 * @param string $message
	 */
	private function assertSamePageEntityUsages( array $expected, array $actual, $message = '' ) {
		if ( $message !== '' ) {
			$message .= "\n";
		}

		foreach ( $expected as $key => $expectedUsages ) {
			Assert::assertArrayHasKey( $key, $actual, 'Page ID' );
			$actualUsages = $actual[$key];

			Assert::assertEquals(
				$expectedUsages->getPageId(),
				$actualUsages->getPageId(),
				$message . "[Page $key] " . 'Page ID mismatches!'
			);
			Assert::assertEquals(
				$expectedUsages->getUsages(),
				$actualUsages->getUsages(),
				$message . "[Page $key] " . 'Usages:'
			);
		}

		Assert::assertEmpty( array_slice( $actual, count( $expected ) ), $message . 'Extra entries found!' );
	}

	public function testGetUnusedEntities() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' );

		$usages = array( $u3i, $u3l, $u4l );

		$this->putUsages( 23, $usages );

		Assert::assertEmpty( $this->lookup->getUnusedEntities( array( $q4 ) ), 'Q4 should not be unused' );

		$entityIds = array( $q4, $q6 );
		if ( wfGetDB( DB_SLAVE )->getType() === 'mysql' ) {
			// On MySQL we use UNIONs on the table… as the table is temporary that
			// doesn't work in unit tests.
			// https://dev.mysql.com/doc/refman/5.7/en/temporary-table-problems.html
			$entityIds = array( $q6 );
		}

		$unused = $this->lookup->getUnusedEntities( $entityIds );
		Assert::assertCount( 1, $unused );
		Assert::assertEquals( $q6, reset( $unused ), 'Q6 should be unused' );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	public function getUsageStrings( array $usages ) {
		$strings = array_map( function( EntityUsage $usage ) {
			return $usage->getIdentityString();
		}, $usages );

		sort( $strings );
		return $strings;
	}

}
