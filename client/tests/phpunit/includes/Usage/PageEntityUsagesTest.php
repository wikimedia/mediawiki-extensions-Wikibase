<?php

namespace Wikibase\Client\Tests\Usage;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Usage\PageEntityUsages
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GNU GPL v2+
 * @author Daniel Kinzler
 */
class PageEntityUsagesTest extends PHPUnit_Framework_TestCase {

	public function testGetters() {
		$q7 = new ItemId( 'Q7' );
		$q11 = new ItemId( 'Q11' );

		$usages = array(
			new EntityUsage( $q7, EntityUsage::ALL_USAGE ),
			new EntityUsage( $q11, EntityUsage::LABEL_USAGE, 'de' ),
			new EntityUsage( $q11, EntityUsage::LABEL_USAGE, 'en' ),
			new EntityUsage( $q11, EntityUsage::TITLE_USAGE ),
		);

		$pageUsages = new PageEntityUsages( 6, $usages );

		$this->assertEquals( 6, $pageUsages->getPageId() );

		$expectedAspects = array(
			EntityUsage::LABEL_USAGE,
			EntityUsage::TITLE_USAGE,
			EntityUsage::ALL_USAGE,
		);

		$expectedAspectKeys = array(
			EntityUsage::LABEL_USAGE . '.de',
			EntityUsage::LABEL_USAGE . '.en',
			EntityUsage::TITLE_USAGE,
			EntityUsage::ALL_USAGE,
		);

		$expectedAspectKeysQ11 = array(
			EntityUsage::LABEL_USAGE . '.de',
			EntityUsage::LABEL_USAGE . '.en',
			EntityUsage::TITLE_USAGE,
		);

		$this->assertEquals( $expectedAspects, $pageUsages->getAspects(), 'getAspects' );
		$this->assertEquals( $expectedAspectKeys, $pageUsages->getAspectKeys(), 'getAspectKeys' );
		$this->assertEquals(
			array( 'Q11' => $q11, 'Q7' => $q7 ),
			$pageUsages->getEntityIds(),
			'getEntityIds'
		);
		$this->assertEquals(
			array( 'Q11#L.de', 'Q11#L.en', 'Q11#T', 'Q7#X' ),
			array_keys( $pageUsages->getUsages() ),
			'getUsagesCallback'
		);

		$this->assertEquals(
			$expectedAspectKeysQ11,
			$pageUsages->getUsageAspectKeys( $q11 ),
			'getUsageAspects'
		);
	}

}
