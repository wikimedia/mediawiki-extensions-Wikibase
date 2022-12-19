<?php

namespace Wikibase\Client\Tests\Integration\Usage;

use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikimedia\Rdbms\IDatabase;

/**
 * Helper class for testing UsageLookup implementations,
 * providing generic tests for the interface's contract.
 *
 * @license GPL-2.0-or-later
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

		$usages = [
			$u3i->getIdentityString() => $u3i,
			$u3l->getIdentityString() => $u3l,
			$u4l->getIdentityString() => $u4l,
		];

		$this->putUsages( 23, array_values( $usages ) );

		Assert::assertSame( [], $this->lookup->getUsagesForPage( 24 ) );

		$actualUsage = $this->lookup->getUsagesForPage( 23 );
		Assert::assertCount( 3, $actualUsage );

		$actualUsageStrings = $this->getUsageStrings( $actualUsage );
		$expectedUsageStrings = $this->getUsageStrings( $usages );
		Assert::assertEquals( $expectedUsageStrings, $actualUsageStrings );

		$this->putUsages( 23, [] );
	}

	public function testGetPagesUsing() {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3s = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' );
		$u4t = new EntityUsage( $q4, EntityUsage::TITLE_USAGE );

		$this->putUsages( 23, [ $u3s, $u3l, $u4l ] );
		$this->putUsages( 42, [ $u4l, $u4t ] );

		$pages = $this->lookup->getPagesUsing( [ $q6 ] );
		Assert::assertSame( [], iterator_to_array( $pages ) );

		$pages = $this->lookup->getPagesUsing( [ $q3 ] );
		$this->assertSamePageEntityUsages(
			[ 23 => new PageEntityUsages( 23, [ $u3s, $u3l ] ) ],
			iterator_to_array( $pages ),
			'Pages using Q3'
		);

		$pages = $this->lookup->getPagesUsing(
			[ $q4, $q3 ],
			[ EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE, 'de' ) ]
		);
		$this->assertSamePageEntityUsages(
			[
				23 => new PageEntityUsages( 23, [ $u3l, $u4l ] ),
				42 => new PageEntityUsages( 42, [ $u4l ] ),
			],
			iterator_to_array( $pages ),
			'Pages using "label" on Q4 or Q3'
		);

		$pages = $this->lookup->getPagesUsing( [ $q3 ], [ EntityUsage::ALL_USAGE ] );
		Assert::assertSame( [], iterator_to_array( $pages ), 'Pages using "all" on Q3' );

		$pages = $this->lookup->getPagesUsing( [ $q4 ], [ EntityUsage::SITELINK_USAGE ] );
		Assert::assertSame( [], iterator_to_array( $pages ), 'Pages using "sitelinks" on Q4' );

		$pages = $this->lookup->getPagesUsing(
			[ $q3, $q4 ],
			[ EntityUsage::TITLE_USAGE, EntityUsage::SITELINK_USAGE ]
		);
		Assert::assertCount(
			2,
			iterator_to_array( $pages ),
			'Pages using "title" or "sitelinks" on Q3 or Q4'
		);

		$this->putUsages( 23, [] );
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

		Assert::assertSame( [], array_slice( $actual, count( $expected ) ), $message . 'Extra entries found!' );
	}

	public function testGetUnusedEntities( IDatabase $db ) {
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );
		$q6 = new ItemId( 'Q6' );

		$u3i = new EntityUsage( $q3, EntityUsage::SITELINK_USAGE );
		$u3l = new EntityUsage( $q3, EntityUsage::LABEL_USAGE, 'de' );
		$u4l = new EntityUsage( $q4, EntityUsage::LABEL_USAGE, 'de' );

		$usages = [ $u3i, $u3l, $u4l ];

		$this->putUsages( 23, $usages );

		Assert::assertSame( [], $this->lookup->getUnusedEntities( [ $q4 ] ), 'Q4 should not be unused' );

		$entityIds = [ $q4, $q6 ];
		if ( $db->getType() === 'mysql' ) {
			// On MySQL we use UNIONs on the table… as the table is temporary that
			// doesn't work in unit tests.
			// https://dev.mysql.com/doc/refman/5.7/en/temporary-table-problems.html
			$entityIds = [ $q6 ];
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

		asort( $strings );
		return $strings;
	}

}
