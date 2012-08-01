<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for the ApiWikibase class.
 *
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * BE WARNED: the tests depend on execution order of the methods and the methods are interdependent,
 * so stuff will fail if you move methods around or make changes to them without matching changes in
 * all methods depending on them.
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
 * @group ApiSetItemTest
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
class ApiSetItemTest extends ApiModifyItemBase {

	function testSetItem() {
		$item = array(
			"sitelinks" => array(
				"dewiki" => "Foo",
				"enwiki" => "Bar",
			),
			"labels" => array(
				"de" => "Foo",
				"en" => "Bar",
			),
			"aliases" => array(
				"de"  => array( "Fuh" ),
				"en"  => array( "Baar" ),
			),
			"descriptions" => array(
				"de"  => "Metasyntaktische Veriable",
				"en"  => "Metasyntactic variable",
			)
		);

		// ---- check failure without token --------------------------
		$this->login();

		if ( self::$usetoken ) {
			try {
				$this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( $item ),
					),
					null,
					false,
					self::$users['wbeditor']->user
				);

				$this->fail( "Adding an item without a token should have failed" );
			}
			catch ( \UsageException $e ) {
				$this->assertTrue( ($e->getCode() == 'session-failure'), "Expected session-failure, got unexpected exception: $e" );
			}
		}

		// ---- check success with token --------------------------
		$token = $this->getItemToken();

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => json_encode( $item ),
				'token' => $token,
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertSuccess( $res, 'item', 'id' );
		$this->assertItemEquals( $item, $res['item'] );

		$id = $res['item']['id'];

		// ---- check failure to set the same item again, without id -----------
		$item['labels'] = array(
			"de" => "Foo X",
			"en" => "Bar Y",
		);

		try {
			$this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'reason' => 'Some reason',
					'data' => json_encode( $item ),
					'token' => $token,
				),
				null,
				false,
				self::$users['wbeditor']->user
			);

			$this->fail( "Adding another item with the same sitelinks should have failed" );
		}
		catch ( \UsageException $e ) {
			$this->assertTrue( ($e->getCode() == 'set-sitelink-failed'), "Expected set-sitelink-failed, got unexpected exception: $e" );
		}

		// ---- check success of update with id --------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => json_encode( $item ),
				'token' => $token,
				'id' => $id,
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertSuccess( $res, 'item', 'id' );
		$this->assertItemEquals( $item, $res['item'] );
	}

	/**
	 * @group API
	 */
	function testGetToken() {
		if ( !static::$usetoken ) {
			$this->markTestSkipped( "tokens disabled" );
			return;
		}

		$data = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'ids' => '23',
				'gettoken' => '' ),
			null,
			false,
			self::$users['wbeditor']->user
		);

		// this should always hold for a logged in user
		// unless we do some additional tricks with the token
		$this->assertEquals(
			34, strlen( $data[0]["wbgetitems"]["itemtoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $data[0]["wbgetitems"]["itemtoken"],
			"The final chars of the token is not '+\\'"
		);
	}

	function testChangeLabel() {
		$this->markTestIncomplete();
	}

	function testChangeDescription() {
		$this->markTestIncomplete();
	}

	function testChangeAlias() {
		$this->markTestIncomplete();
	}

	function testChangeSitelink() {
		$this->markTestIncomplete();
	}

	/*
	function testProtectedItem() { //TODO
		$this->markTestIncomplete();
	}

	function testBlockedUser() { //TODO
		$this->markTestIncomplete();
	}

	function testEditPermission() { //TODO
		$this->markTestIncomplete();
	}
	*/

}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class DummyFoo extends ApiSetItemTest{


	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeUpdate( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeRemove( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'remove' );
	}

	public function setLanguageAttribute( $id, $site, $title, $language, $label, $description, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);

			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}
		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
			'id' => "{$myid}",
			'label' => ($op === 'remove' ? '' : $label),
			'description' => ($op === 'remove' ? '' : $description),
			'language' => $language,
			'usekeys' => true,
			'format' => 'jsonfm',
		) );

		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );
		if ( $op === 'remove' ) {
			$this->assertArrayHasKey( $language, $first[0]['item']['labels'],
				"Must have an '{$language}' key in the 'labels' result in the first call to the API" );
			$this->assertArrayHasKey( 'removed', $first[0]['item']['labels'][$language],
				"Must have a key 'removed' in the '{$language}' for labels' set in the result in the first call to the API" );
			$this->assertArrayHasKey( $language, $first[0]['item']['descriptions'],
				"Must have an '{$language}' key in the 'descriptions' result in the first call to the API" );
			$this->assertArrayHasKey( 'removed', $first[0]['item']['descriptions'][$language],
				"Must have the value 'removed' in the '{$language}' for 'descriptions' set in the result in the first call to the API" );
		}
		else {
			$this->assertArrayHasKey( $language, $first[0]['item']['labels'],
				"Must have an '{$language}' key in the 'labels' result in the first call to the API" );
			$this->assertEquals( $label, $first[0]['item']['labels'][$language]['value'],
				"Must have the value '{$label}' for the value of '{$language}' in the 'labels' set in the result in the first call to the API" );
			$this->assertArrayHasKey( $language, $first[0]['item']['descriptions'],
				"Must have an '{$language}' key in the 'descriptions' result in the first call to the API" );
			$this->assertEquals( $description, $first[0]['item']['descriptions'][$language]['value'],
				"Must have the value '{$description}' for the value of '{$language}' in the 'descriptions' set in the result in the first call to the API" );
		}

		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $myid,
			'usekeys' => true,
			'format' => 'jsonfm',
			'language' => $language,
		) );

		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result in the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result in the second call to the API" );
		if ( $op === 'remove' ) {
			$this->assertFalse( array_key_exists( $language, $second[0]['items']["{$myid}"]['labels'] ),
				"Must have a '{$language}' key in the 'labels' result in the second call to the API" );
			$this->assertFalse( array_key_exists( $language, $second[0]['items']["{$myid}"]['descriptions'] ),
				"Must have an '{$language}' key in the 'descriptions' result in the second call to the API" );
		}
		else {
			$this->assertTrue( array_key_exists( $language, $second[0]['items']["{$myid}"]['labels'] ),
				"Must have an '{$language}' key in the 'labels' result in the second call to the API" );
			$this->assertTrue( array_key_exists( $language, $second[0]['items']["{$myid}"]['descriptions'] ),
				"Must have an '{$language}' key in the 'descriptions' result in the second call to the API" );
		}
	}

	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider,
	 * and then deletes the content of the given language attribute because its empty.
	 * That is the updating should not have moved them around or deleted old content.
	 *
	 * @group API
	 * @dataProvider providerEmptyLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testEmptyLanguageAttributeLabel( $id, $site, $title, $language ) {
		$this->emptyLanguageAttribute( $id, $site, $title, $language, 'label' );
	}

	/**
	 * @group API
	 * @dataProvider providerEmptyLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testEmptyLanguageAttributeDescription( $id, $site, $title, $language ) {
		$this->emptyLanguageAttribute( $id, $site, $title, $language, 'description' );
	}

	public function emptyLanguageAttribute( $id, $site, $title, $language, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}

		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
			'id' => $myid,
			$op => '',
			'language' => $language,
		) );

		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );

		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );

		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $myid,
			'language' => $language,
		) );

		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result in the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result in the second call to the API" );

		if ( isset($second[0]['items']["{$myid}"][$op . 's'][$language]) ) {
			$this->fail( "Must not have an '{$language}' key in the '{$op}s' result in the second call to the API" );
		}
	}

	public function providerGetItemsMissingTitle() {

		$idx = self::$baseOfItemIds;
		return array(
			array( $idx, 'dewiki', 'sugarinthemorningtothefriedkittens' ),
			array( $idx, 'dewiki', 'sugarinthemorningtothefriedkittens|sugarintheeveningtothefriedkittens' ),
			array( $idx, 'dewiki|enwiki', 'sugarinthemorningtothefriedkittens' ),
		);
	}

	public function providerGetItemId() {

		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin' ),
			array( $idx, 'enwiki', 'Berlin' ),
			array( $idx, 'nlwiki', 'Berlin' ),
			array( $idx, 'nnwiki', 'Berlin' ),
			array( $idx, 'dewiki|enwiki|nlwiki|nnwiki', 'Berlin' ),
			array( $idx, 'dewiki', ' Berlin' ),
			array( $idx, 'enwiki', '  Berlin' ),
			array( $idx, 'nlwiki', 'Berlin ' ),
			array( $idx, 'nnwiki', 'Berlin  ' ),
			array( ++$idx, 'dewiki', 'London' ),
			array( $idx, 'enwiki', 'London' ),
			array( $idx, 'nlwiki', 'London' ),
			array( $idx, 'dewiki|enwiki|nlwiki|nnwiki', 'London' ),
			array( $idx, 'nnwiki', 'London' ),
			array( $idx, 'dewiki', ' London ' ),
			array( $idx, 'enwiki', '  London  ' ),
			array( $idx, 'nlwiki', '   London   ' ),
			array( $idx, 'nnwiki', '    London    ' ),
			array( ++$idx, 'enwiki', 'Oslo' ),
			array( $idx, 'dewiki', 'Oslo' ),
			array( $idx, 'nlwiki', 'Oslo' ),
			array( $idx, 'nnwiki', 'Oslo' ),
			array( $idx, 'enwiki|dewiki|nlwiki|nnwiki', 'Oslo' ),
			array( ++$idx, 'enwiki', 'Episkopi Cantonment' ),
			array( $idx, 'dewiki', 'Episkopi  Cantonment' ),
			array( $idx, 'nlwiki', 'Episkopi   Cantonment' ),
		);
	}

	public function providerLinkSiteId() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin', 'enwiktionary', 'Berlin', 1 ),
			array( ++$idx, 'enwiki', 'London', 'enwiktionary', 'London', 2 ),
			array( ++$idx, 'nlwiki', 'Oslo', 'enwiktionary', 'Oslo', 3 ),
		);
	}

	public function providerLinkSitePair() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin', 'svwiki', 'Berlin', 1 ),
			array( ++$idx, 'enwiki', 'London', 'svwiki', 'London', 2 ),
			array( ++$idx, 'nlwiki', 'Oslo', 'svwiki', 'Oslo', 3 ),
		);
	}

	public function providerLabelDescription() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'nlwiki', 'Berlin', 'no', 'Berlin', 'Hovedstad i Tyskland' ),
			array( ++$idx, 'nlwiki', 'London', 'nn', 'London', 'Hovudstad i England' ),
			array( ++$idx, 'nlwiki', 'Oslo', 'en', 'Oslo', 'Capitol in Norway' ),
		);
	}

	public function providerEmptyLabelDescription() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'nl' ),
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'en' ),
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'de' ),
		);
	}

}
