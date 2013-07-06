<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for setting sitelinks throug from-to -pairs.
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
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group LinkTitlesTest
 * @group BreakingTheSlownessBarrier
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
class LinkTitlesTest extends ModifyEntityBase {

	public function testLinkTitlesWithNoToken( ) {
		if ( !self::$usetoken ) {
			$this->markTestSkipped( "tokens disabled" );
			return;
		}

		$req = array(
			'action' => 'wblinktitles',
			'fromsite' => "enwiki",
			'fromtitle' => "testLinkTitlesWithNoToken",
			'tosite' => "enwiki",
			'totitle' => "testLinkTitlesWithEvenLessToken",
		);

		try {
			$this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public static function provideLinkTitles() {
		return array(
			// Oslo should already exist, add nowiki
			array( 'Oslo', // handle
				array(), // by id
				'nnwiki', // already set
				'Oslo',
				'nowiki', // adding this one
				'Oslo',
			),
			// Oslo should already exist, add svwiki
			array( 'Oslo', // handle
				array(), // by id
				'svwiki', // adding this one
				'Oslo',
				'nnwiki', // already set
				'Oslo',
			),
			// Try to add two existing ones, should fail with a 'common-item'
			array( 'Oslo', // handle
				array(), // by id
				'nnwiki', // already set
				'Oslo',
				'nowiki', // already set
				'Oslo',
				'common-item'
			),
			// Try to add an existing one from another item, should fail with a 'no-common-item'
			array( 'Oslo', // handle
				array(), // by id
				'nnwiki', // already set
				'Oslo',
				'nnwiki', // already set, from another item
				'Berlin',
				'no-common-item'
			),
			// Try to add an existing one from another item, should fail with a 'no-common-item'
			array( null, // handle
				array(), // by id
				'nnwiki', // already set
				'Hammerfest',
				'nnwiki', // already set, from another item
				'Hammerfest',
				'fromsite-eq-tosite'
			),
			// Try to add an existing one from another item, should fail with a 'no-common-item'
			array( null, // handle
				array(), // by id
				'nnwiki', // already set
				'Bergen',
				'nowiki', // already set, from another item
				'Bergen',
			),
		);
	}

	/**
	 * @dataProvider provideLinkTitles
	 */
	public function testLinkTitles( $handle, $item_spec, $fromsite, $fromtitle, $tosite, $totitle, $expectedFailure = null ) {
		$token = $this->getEntityToken();
		if ( $handle ) {
			$id = $this->getEntityId( $handle );
			$this->resetEntity( $handle ); //nasty. we shouldn't need to do this. But apparently some other test spills bad state.
		}

		// set the sitelink -------------------------------
		$req = array_merge( $item_spec, array(
			'action' => 'wblinktitles',
			'token' => $token,
			'fromsite' => $fromsite,
			'fromtitle' => $fromtitle,
			'tosite' => $tosite,
			'totitle' => $totitle,
		) );

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			if ( $expectedFailure ) {
				$this->fail( "Expected failure: $expectedFailure" );
			}

			// check the response -------------------------------
			$this->assertEquals( \Wikibase\Item::ENTITY_TYPE,  $res['entity']['type'] );
			if ( $handle ) {
				$this->assertEquals( 1, count( $res['entity']['sitelinks'] ), "expected exactly one sitelinks structure" );
			}
			else {
				$this->assertEquals( 2, count( $res['entity']['sitelinks'] ), "expected exactly two sitelinks structure" );
			}

			$this->assertArrayHasKey( 'lastrevid', $res['entity'] , 'entity should contain lastrevid key' );

			foreach ( $res['entity']['sitelinks'] as $link ) {
				$this->assertTrue( $fromsite === $link['site'] || $tosite === $link['site'] );
				$this->assertTrue( $fromtitle === $link['title'] || $totitle === $link['title'] );
			}

			// check the item in the database -------------------------------
			if ( isset( $id ) ) {
				$item = $this->loadEntity( $id );
				$links = self::flattenArray( $item['sitelinks'], 'site', 'title' );
				$this->assertEquals( $fromtitle, $links[$fromsite], 'wrong link target' );
				$this->assertEquals( $totitle, $links[$tosite], 'wrong link target' );
			}

		} catch ( \UsageException $e ) {
			if ( !$expectedFailure ) {
				$this->fail( "unexpected exception: $e" );
			}
		}

		// clean up, but note that we can't clean up newly created items -------------------------------
		if ( $handle ) {
			$this->resetEntity( $handle );
		}

		$this->assertTrue( true );
	}

}
