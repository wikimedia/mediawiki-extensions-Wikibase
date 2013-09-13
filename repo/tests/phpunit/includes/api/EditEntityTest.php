<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for the ApiWikibase class.

 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group EditEntityTest
 * @group BreakingTheSlownessBarrier
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group large
 */
class EditEntityTest extends WikibaseApiTestCase {

	private static $testEntityId;
	private static $hasSetup;
	static public $id = null;

	public function setup() {
		parent::setup();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Berlin' ) );
		}
		self::$hasSetup = true;
	}

		public static function provideData() {
		return array(
			array( //0 new item
				'p' => array( 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'type' => 'item' ) ),
			array( //1 new property
				'p' => array( 'new' => 'property', 'data' => '{"datatype":"string"}' ),
				'e' => array( 'type' => 'property' ) ),
			array( //2 new property (this is our current example in the api doc)
				'p' => array( 'new' => 'property', 'data' => '{"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},'.
				'"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},"datatype":"string"}' ),
				'e' => array( 'type' => 'property' ) ),
			array( //3 add a sitelink..
				'p' => array( 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!"}}}' ),
				'e' => array( 'sitelinks' => array( 'dewiki' => 'TestPage!' ) ) ),
			array( //4 add a label..
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":"A Label"}}}' ),
				'e' => array( 'sitelinks' => array( 'dewiki' => 'TestPage!' ), 'labels' => array( 'en' => 'A Label' ) ) ),
			array( //5 add a description..
				'p' => array( 'data' => '{"descriptions":{"en":{"language":"en","value":"DESC"}}}' ),
				'e' => array( 'sitelinks' => array( 'dewiki' => 'TestPage!' ), 'labels' => array( 'en' => 'A Label' ), 'descriptions' => array( 'en' => 'DESC' ) ) ),
			array( //6 remove a sitelink..
				'p' => array( 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}' ),
				'e' => array( 'labels' => array( 'en' => 'A Label' ), 'descriptions' => array( 'en' => 'DESC' ) ) ),
			array( //7 remove a label..
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":""}}}' ),
				'e' => array( 'descriptions' => array( 'en' => 'DESC' ) ) ),
			array( //8 remove a description..
				'p' => array( 'data' => '{"descriptions":{"en":{"language":"en","value":""}}}' ),
				'e' => array( 'type' => 'item' ) ),
			array( //9 clear an item with some new value
				'p' => array( 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"page"}}}', 'clear' => '' ),
				'e' => array( 'type' => 'item', 'sitelinks' => array( 'dewiki' => 'Page' ) ) ),
			array( //10 clear an item with no value
				'p' => array( 'data' => '{}', 'clear' => '' ),
				'e' => array( 'type' => 'item' ) ),
			array( //11 add 2 labels
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":"A Label"},"sv":{"language":"sv","value":"SVLabel"}}}' ),
				'e' => array( 'labels' => array( 'en' => 'A Label', 'sv' => 'SVLabel' ) ) ),
			array( //12 override and add 2 descriptions
				'p' => array( 'clear' => '', 'data' => '{"descriptions":{"en":{"language":"en","value":"DESC1"},"de":{"language":"de","value":"DESC2"}}}' ),
				'e' => array( 'descriptions' => array( 'en' => 'DESC1', 'de' => 'DESC2' ) ) ),
			array( //13 override and add a 2 sitelinks..
				'p' => array( 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"BAA"},"svwiki":{"site":"svwiki","title":"FOO"}}}' ),
				'e' => array( 'sitelinks' => array( 'dewiki' => 'BAA', 'svwiki' => 'FOO' ) ) ),
			array( //14 unset a sitelink using the other sitelink
				'p' => array( 'site' => 'svwiki', 'title' => 'FOO', 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}' ),
				'e' => array( 'sitelinks' => array( 'svwiki' => 'FOO' ) ) ),
		);
	}

	/**
	 * @dataProvider provideData
	 */
	function testEditEntity( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		if( !array_key_exists( 'id', $params )
			&& !array_key_exists( 'new', $params )
			&& !array_key_exists( 'site', $params )
			&& !array_key_exists( 'title', $params) ){
			$params['id'] = self::$testEntityId;
		}

		// -- do the request --------------------------------------------------
		list($result,,) = $this->doApiRequestWithToken( $params );

		// -- steal stuff for later tests -------------------------------------
		if( array_key_exists( 'new', $params ) && stristr( $params['new'], 'item' ) ){
			self::$testEntityId = $result['entity']['id'];
		}

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'id', $result['entity'], "Missing 'id' section in entity in response." );
		$this->assertEntityEquals( $expected, $result['entity'] );

		// -- check the item in the database -------------------------------
		$dbEntity = $this->loadEntity( $result['entity']['id'] );
		$this->assertEntityEquals( $expected, $dbEntity );

		// -- check the edit summary --------------------------------------------
		if( !array_key_exists( 'warning', $expected ) || $expected['warning'] != 'edit-no-change' ){
			$this->assertRevisionSummary( array( 'wbeditentity' ), $result['entity']['lastrevid'] );
			if( array_key_exists( 'summary', $params) ){
				$this->assertRevisionSummary( "/{$params['summary']}/" , $result['entity']['lastrevid'] );
			}
		}
	}

	public static function provideExceptionData() {
		return array(
			array( //0 no entity id given
				'p' => array( 'id' => '', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id' ) ) ),
			array( //1 invalid id
				'p' => array( 'id' => 'abcde', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id' ) ) ),
			array( //2 invalid explicit id
				'p' => array( 'id' => '1234', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id' ) ) ),
			array( //3 non existent sitelink
				'p' => array( 'site' => 'dewiki','title' => 'NonExistent', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-link' ) ) ),
			array( //4 missing site (also bad title)
				'p' => array( 'title' => 'abcde', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //5 cant have id and new
				'p' => array( 'id' => 'q666', 'new' => 'item' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //6 when clearing must also have data!
				'p' => array( 'site' => 'enwiki', 'new' => 'Berlin', 'clear' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal' ) ) ),
			array( //7 bad site
				'p' => array( 'site' => 'abcde', 'data' => '{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_site' ) ) ),
			array( //8 no data provided
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-data' ) ) ),
			array( //9 malformed json
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '{{{}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'invalid-json' ) ) ),
			array( //10 must be a json object (json_decode s this an an int)
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '1234'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-recognized-array' ) ) ),
			array( //11 must be a json object (json_decode s this an an indexed array)
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '[ "xyz" ]'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-recognized-string' ) ) ),
			array( //12 must be a json object (json_decode s this an a string)
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '"string"'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-recognized-array' ) ) ),
			array( //13 inconsistent site in json
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '{"sitelinks":{"ptwiki":{"site":"svwiki","title":"TestPage!"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'inconsistent-site' ) ) ),
			array( //14 inconsistent lang in json
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '{"labels":{"de":{"language":"pt","value":"TestPage!"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'inconsistent-language' ) ) ),
			array( //15 inconsistent unknown site in json
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '{"sitelinks":{"BLUB":{"site":"BLUB","title":"TestPage!"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-recognized-site' ) ) ),
			array( //16 inconsistent unknown languages
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' , 'data' => '{"lables":{"BLUB":{"language":"BLUB","value":"ImaLabel"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-recognized' ) ) ),
			//@todo the error codes in the overly long string tests make no sense and should be corrected...
			array( //17 overly long label
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' ,
					'data' => '{"lables":{"en":{"language":"en","value":"'.LangAttributeTestHelper::makeOverlyLongString().'"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			array( //18 overly long description
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' ,
					'data' => '{"descriptions":{"en":{"language":"en","value":"'.LangAttributeTestHelper::makeOverlyLongString().'"}}}'),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			//@todo add check for Bug:52731 once fixed
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testEditEntityExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}
