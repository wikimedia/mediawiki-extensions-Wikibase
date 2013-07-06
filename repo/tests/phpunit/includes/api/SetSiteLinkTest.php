<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Additional tests for ApiLinkSite API module.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group SetSiteLinkTest
 * @group BreakingTheSlownessBarrier
 * @group XXX
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class SetSiteLinkTest extends ModifyEntityTestBase {

	public function setup() {
		parent::setup();

		static $hasSites;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}
	}

	public function testSetLiteLinkWithNoId( ) {
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoId",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithBadId( ) {
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'id' => 123456789,
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoId",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithBadSite( ) {
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'site' => "dewiktionary",
			'title' => "Berlin",
			'linksite' => "enwiki",
			'linktitle' => "Berlin",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithBadTitle( ) {
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'site' => "dewiki",
			'title' => "testSetLiteLinkWithBadTitle_de",
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithBadTitle_en",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithNoToken( ) {
		if ( !self::$usetoken ) {
			$this->markTestSkipped( "tokens disabled" );
			return;
		}

		$req = array(
			'action' => 'wbsetsitelink',
			'id' => $this->getEntityId( "Berlin" ),
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoToken",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public static function provideSetLiteLink() {
		return array(
			array( 'Leipzig', // handle
				array( 'id' => null ), // by id
				'dewiki', // not yet set
				'leipzig', // will be normalized
				'Leipzig'
			),
			array( 'Berlin', // handle
				array( 'id' => null ), // by id
				'enwiki', // already set
				'Potsdam', // replaces value
				'Potsdam'
			),
			array( 'London', // handle
				array( 'site' => 'enwiki', 'title' => 'London' ), // by sitelink
				'enwiki', // already set
				'', // remove the entry
				'London'
			),
			array( 'London', // handle
				array( 'site' => 'dewiki', 'title' => 'London' ), // by sitelink
				'svwiki', // not set
				'', // remove the entry
				false, // expect nothing
			),
		);
	}

	/**
	 * @dataProvider provideSetLiteLink
	 */
	public function testSetLiteLink( $handle, $item_spec, $linksite, $linktitle, $expectedTitle = null, $expectedFailure = null ) {
		$token = $this->getEditToken();
		$id = $this->getEntityId( $handle );

		$this->resetEntity( $handle ); //nasty. we shouldn't need to do this. But apparently some other test spills bad state.

		if ( array_key_exists( 'id', $item_spec ) && empty( $item_spec['id'] ) ) {
			//NOTE: data provider is called before setUp and thus can't determine IDs.
			//      So fill in the missing IDs here.
			$item_spec['id'] = $id;
		}

		if ( $expectedTitle === null ) {
			$expectedTitle = $linktitle;
		}

		// set the sitelink -------------------------------
		$req = array_merge( $item_spec, array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
		) );

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			if ( $expectedFailure ) {
				$this->fail( $expectedFailure );
			}

			// check the response -------------------------------
			//$this->assertSuccess( $res, 'entity', 'sitelinks', 0 );
			if ( $expectedTitle !== false ) {
				$this->assertEquals( 1, count( $res['entity']['sitelinks'] ), "expected exactly one sitelinks structure" );
			}

			$this->assertArrayHasKey( 'lastrevid', $res['entity'] , 'entity should contain lastrevid key' );

			if ( $expectedTitle !== false ) {
				$link = array_shift( $res['entity']['sitelinks'] );
				$this->assertEquals( $linksite, $link['site'] );
			} else {
				$link = null;
			}

			if ( $linktitle === '' && $link !== null ) {
				$this->assertArrayHasKey( 'removed', $link );
			}

			if ( $expectedTitle !== false ) {
				$this->assertEquals( $expectedTitle, $link['title'] );
			}

			if ( $expectedTitle !== false && $linktitle !== '' ) {
				$this->assertArrayHasKey( 'url', $link );
			}
			elseif ( $link !== null ) {
				$this->assertArrayNotHasKey( 'url', $link );
			}
		} catch ( \UsageException $e ) {
			if ( !$expectedFailure ) {
				$this->fail( "unexpected exception: $e" );
			}
		}

		// check the item in the database -------------------------------
		$item = $this->loadEntity( $id );
		$links = self::flattenArray( $item['sitelinks'], 'site', 'title' );

		if ( $linktitle === '' ) {
			$this->assertArrayNotHasKey( $linksite, $links, 'link should have been removed' );
		} else {
			$this->assertArrayHasKey( $linksite, $links, 'link went missing' );

			if ( $expectedTitle !== false ) {
				$this->assertEquals( $expectedTitle, $links[$linksite], 'wrong link target' );
			}
		}

		// clean up -------------------------------
		$this->resetEntity( $handle );
	}

	public function testSetLiteLinkWithBadTargetSite( ) {
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'site' => "dewiki",
			'title' => "Berlin",
			'linksite' => "enwiktionary",
			'linktitle' => "Berlin",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}
}

