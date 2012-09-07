<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\ApiSetSiteLink;

/**
 * Additional tests for ApiLinkSite API module.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiGetItemsTest
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
class ApiSetSiteLinkTest extends ApiModifyItemBase {

	public function testSetLiteLinkWithNoId( ) {
		$token = $this->getItemToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoId",
		);

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithBadId( ) {
		$token = $this->getItemToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'id' => 123456789,
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoId",
		);

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function testSetLiteLinkWithBadTitle( ) {
		$token = $this->getItemToken();

		$req = array(
			'action' => 'wbsetsitelink',
			'token' => $token,
			'site' => "dewiki",
			'title' => "testSetLiteLinkWithBadTitle_de",
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithBadTitle_en",
		);

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

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
			'id' => $this->getItemId( "Berlin" ),
			'linksite' => "enwiki",
			'linktitle' => "testSetLiteLinkWithNoToken",
		);

		try {
			list( $res,, ) = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

			$this->fail( "request should have failed" );
		} catch ( \UsageException $e ) {
			$this->assertTrue( true ); // ok
		}
	}

	public function provideSetLiteLink() {
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
				false, // will fail
				'should not be able to remove non-existing link' //XXX: shouldn't that rather be a warning?!
			),
		);
	}

	/**
	 * @dataProvider provideSetLiteLink
	 */
	public function testSetLiteLink( $handle, $item_spec, $linksite, $linktitle, $expectedTitle = null, $expectedFailure = null ) {
		$token = $this->getItemToken();
		$id = $this->getItemId( $handle );

		$this->resetItem( $handle ); //nasty. we shouldn't need to do this. But apparently some other test spills bad state.

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
			$this->assertSuccess( $res, 'item', 'sitelinks', 0 );
			$this->assertEquals( 1, count( $res['item']['sitelinks'] ), "expected exactly one sitelinks structure" );

			$link = $res['item']['sitelinks'][0];
			$this->assertEquals( $linksite, $link['site'] );

			if ( $linktitle === '' ) {
				$this->assertArrayHasKey( 'removed', $link );
			}

			if ( $expectedTitle !== false ) {
				$this->assertEquals( $expectedTitle, $link['title'] );
			}

			if ( $expectedTitle !== false && $linktitle !== '' ) {
				$this->assertArrayHasKey( 'url', $link );
				// this makes an assumption that the title is represented as a string that does not need
				// normalization or url encoding
				$this->assertContains( $link['title'], $link['url'] );
			}
			else {
				$this->assertArrayNotHasKey( 'url', $link );
			}
		} catch ( \UsageException $e ) {
			if ( !$expectedFailure ) {
				$this->fail( "unexpected exception: $e" );
			}
		}

		// check the item in the database -------------------------------
		$item = $this->loadItem( $id );
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
		$this->resetItem( $handle );
	}

}

