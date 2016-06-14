<?php

namespace Wikibase\Test\Repo\Api;

use UsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\MediaInfo\DataModel\MediaInfo;
use Wikibase\MediaInfo\DataModel\MediaInfoId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\EditEntity
 * @covers Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Michal Lazowik
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group EditEntityTest
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class EditEntityTest extends WikibaseApiTestCase {

	/**
	 * @var string[]
	 */
	private static $idMap;

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$store = $wikibaseRepo->getEntityStore();

			$prop = Property::newFromType( 'string' );
			$store->saveEntity( $prop, 'EditEntityTestP56', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['%P56%'] = $prop->getId()->getSerialization();
			self::$idMap['%StringProp%'] = $prop->getId()->getSerialization();

			$prop = Property::newFromType( 'string' );
			$store->saveEntity( $prop, 'EditEntityTestP72', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['%P72%'] = $prop->getId()->getSerialization();

			$this->initTestEntities( array( 'Berlin' ), self::$idMap );
			self::$idMap['%Berlin%'] = EntityTestHelper::getId( 'Berlin' );

			$p56 = self::$idMap['%P56%'];
			$berlinData = EntityTestHelper::getEntityOutput( 'Berlin' );
			self::$idMap['%BerlinP56%'] = $berlinData['claims'][$p56][0]['id'];

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ42', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['%Q42%'] = $badge->getId()->getSerialization();

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ149', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['%Q149%'] = $badge->getId()->getSerialization();

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ32', $GLOBALS['wgUser'], EDIT_NEW );
			self::$idMap['%Q32%'] = $badge->getId()->getSerialization();

			$wikibaseRepo->getSettings()->setSetting( 'badgeItems', array(
				self::$idMap['%Q42%'] => '',
				self::$idMap['%Q149%'] => '',
				'Q99999' => '', // Just in case we have a wrong config
			) );

			// Create a file page for which we can later create a MediaInfo entity.
			// XXX It's ugly to have knowledge about MediaInfo here. But since we currently can't
			// inject mock handlers for a mock media type, this is the only way to test automatic
			// creation.

			$titleInfo = $this->insertPage( 'File:EditEntityTest.jpg' );
			self::$idMap['%M11%'] = 'M' . $titleInfo['id'];
		}
		self::$hasSetup = true;
	}

	/**
	 * Provide data for a sequence of requests that will work when run in order
	 * @return array
	 */
	public function provideData() {
		return array(
			'new item' => array(
				'p' => array( 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'type' => 'item' ) ),
			'new property' => array( // make sure if we pass in a valid type it is accepted
				'p' => array( 'new' => 'property', 'data' => '{"datatype":"string"}' ),
				'e' => array( 'type' => 'property' ) ),
			'new property with data' => array( // this is our current example in the api doc
				'p' => array(
					'new' => 'property',
					'data' => '{"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},'
						. '"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},'
						. '"datatype":"string"}'
				),
				'e' => array( 'type' => 'property' ) ),
			'new mediainfo from id' => array(
				'p' => array( 'id' => '%M11%', 'data' => '{}' ),
				'e' => array( 'type' => 'mediainfo' ),
				't' => 'mediainfo', // skip if MediaInfo is not configured
			),
			'add a sitelink..' => array( // make sure if we pass in a valid id it is accepted
				'p' => array(
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki",'
						. '"title":"TestPage!","badges":["%Q42%","%Q149%"]}}}'
				),
				'e' => array(
					'sitelinks' => array(
						array(
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => array( '%Q42%', '%Q149%' )
						)
					)
				)
			),
			'add a label..' => array(
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":"A Label"}}}' ),
				'e' => array(
					'sitelinks' => array(
						array(
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => array( '%Q42%', '%Q149%' )
						)
					),
					'labels' => array( 'en' => 'A Label' )
				)
			),
			'add a description..' => array(
				'p' => array( 'data' => '{"descriptions":{"en":{"language":"en","value":"DESC"}}}' ),
				'e' => array(
					'sitelinks' => array(
						array(
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => array( '%Q42%', '%Q149%' )
						)
					),
					'labels' => array( 'en' => 'A Label' ),
					'descriptions' => array( 'en' => 'DESC' )
				)
			),
			'remove a sitelink..' => array(
				'p' => array( 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}' ),
				'e' => array(
					'labels' => array( 'en' => 'A Label' ),
					'descriptions' => array( 'en' => 'DESC' ) )
				),
			'remove a label..' => array(
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":""}}}' ),
				'e' => array( 'descriptions' => array( 'en' => 'DESC' ) ) ),
			'remove a description..' => array(
				'p' => array( 'data' => '{"descriptions":{"en":{"language":"en","value":""}}}' ),
				'e' => array( 'type' => 'item' ) ),
			'clear an item with some new value' => array(
				'p' => array(
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"page"}}}',
					'clear' => ''
				),
				'e' => array(
					'type' => 'item',
					'sitelinks' => array(
						array(
							'site' => 'dewiki',
							'title' => 'Page',
							'badges' => array()
						)
					)
				)
			),
			'clear an item with no value' => array(
				'p' => array( 'data' => '{}', 'clear' => '' ),
				'e' => array( 'type' => 'item' ) ),
			'add 2 labels' => array(
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","value":"A Label"},'
					. '"sv":{"language":"sv","value":"SVLabel"}}}' ),
				'e' => array( 'labels' => array( 'en' => 'A Label', 'sv' => 'SVLabel' ) ) ),
			'remove a label with remove' => array(
				'p' => array( 'data' => '{"labels":{"en":{"language":"en","remove":true}}}' ),
				'e' => array( 'labels' => array( 'sv' => 'SVLabel' ) ) ),
			'override and add 2 descriptions' => array(
				'p' => array( 'clear' => '', 'data' => '{"descriptions":{'
					. '"en":{"language":"en","value":"DESC1"},'
					. '"de":{"language":"de","value":"DESC2"}}}' ),
				'e' => array( 'descriptions' => array( 'en' => 'DESC1', 'de' => 'DESC2' ) ) ),
			'remove a description with remove' => array(
				'p' => array( 'data' => '{"descriptions":{"en":{"language":"en","remove":true}}}' ),
				'e' => array( 'descriptions' => array( 'de' => 'DESC2' ) ) ),
			'override and add 2 sitelinks..' => array(
				'p' => array( 'data' => '{"sitelinks":{'
					. '"dewiki":{"site":"dewiki","title":"BAA"},'
					. '"svwiki":{"site":"svwiki","title":"FOO"}}}' ),
				'e' => array(
					'type' => 'item',
					'sitelinks' => array(
						array(
							'site' => 'dewiki',
							'title' => 'BAA',
							'badges' => array()
						),
						array(
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => array()
						)
					)
				)
			),
			'unset a sitelink using the other sitelink' => array(
				'p' => array(
					'site' => 'svwiki',
					'title' => 'FOO',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}'
				),
				'e' => array(
					'type' => 'item',
					'sitelinks' => array(
						array(
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => array()
						)
					)
				)
			),
			'set badges for a existing sitelink, title intact' => array(
				'p' => array(
					'data' => '{"sitelinks":{"svwiki":{"site":"svwiki","badges":["%Q149%","%Q42%"]}}}'
				),
				'e' => array(
					'type' => 'item',
					'sitelinks' => array(
						array(
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => array( "%Q149%", "%Q42%" )
						)
					)
				)
			),
			'set title for a existing sitelink, badges intact' => array(
				'p' => array( 'data' => '{"sitelinks":{"svwiki":{"site":"svwiki","title":"FOO2"}}}' ),
				'e' => array(
					'type' => 'item',
					'sitelinks' => array(
						array(
							'site' => 'svwiki',
							'title' => 'FOO2',
							'badges' => array( "%Q149%", "%Q42%" )
						)
					)
				)
			),
			'delete sitelink by providing neither title nor badges' => array(
				'p' => array( 'data' => '{"sitelinks":{"svwiki":{"site":"svwiki"}}}' ),
				'e' => array(
					'type' => 'item',
				)
			),
			'add a claim' => array(
				'p' => array( 'data' => '{"claims":[{"mainsnak":{"snaktype":"value",'
					. '"property":"%P56%","datavalue":{"value":"imastring","type":"string"}},'
					. '"type":"statement","rank":"normal"}]}' ),
				'e' => array( 'claims' => array(
					'%P56%' => array(
						'mainsnak' => array(
							'snaktype' => 'value',
							'property' => '%P56%',
							'datavalue' => array( 'value' => 'imastring', 'type' => 'string' )
						),
						'type' => 'statement',
						'rank' => 'normal'
					)
				) )
			),
			'change the claim' => array(
				'p' => array( 'data' => array(
					'claims' => array(
							array(
								'id' => '%lastClaimId%',
								'mainsnak' => array(
									'snaktype' => 'value',
									'property' => '%P56%',
									'datavalue' => array(
										'value' => 'diffstring',
										'type' => 'string'
									),
								),
								'type' => 'statement',
								'rank' => 'normal',
							),
						),
					) ),
				'e' => array( 'claims' => array(
					'%P56%' => array(
						'mainsnak' => array( 'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => array(
								'value' => 'diffstring',
								'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal'
					)
				) )
			),
			'remove the claim' => array(
				'p' => array( 'data' => '{"claims":[{"id":"%lastClaimId%","remove":""}]}' ),
				'e' => array( 'claims' => array() )
			),
			'add multiple claims' => array(
				'p' => array( 'data' => '{"claims":['
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring1","type":"string"}},"type":"statement","rank":"normal"},'
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring2","type":"string"}},"type":"statement","rank":"normal"}'
					. ']}' ),
				'e' => array( 'claims' => array(
					array(
						'mainsnak' => array(
							'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => array(
								'value' => 'imastring1',
								'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal' ),
					array(
						'mainsnak' => array(
							'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => array(
								'value' => 'imastring2',
								'type' => 'string' ) ),
						'type' => 'statement',
						'rank' => 'normal' )
				) ),
			),
			'remove all stuff' => array(
				'p' => array( 'clear' => '', 'data' => '{}' ),
				'e' => array(
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'sitelinks' => array(),
					'claims' => array()
				)
			),
			'add lots of data again' => array(
				'p' => array( 'data' => '{"claims":['
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring1","type":"string"}},"type":"statement","rank":"normal"},'
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring2","type":"string"}},"type":"statement","rank":"normal"}'
					. '],'
					. '"sitelinks":{"dewiki":{"site":"dewiki","title":"page"}},'
					. '"labels":{"en":{"language":"en","value":"A Label"}},'
					. '"descriptions":{"en":{"language":"en","value":"A description"}}}' ),
				'e' => array( 'type' => 'item' )
			),
			'make a null edit' => array(
				'p' => array( 'data' => '{}' ),
				'e' => array( 'nochange' => '' )
			),
			'remove all stuff in another way' => array(
				'p' => array( 'clear' => true, 'data' => '{}' ),
				'e' => array(
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'sitelinks' => array(),
					'claims' => array()
				)
			),
		);
	}

	/**
	 * Applies self::$idMap to all data in the given data structure, recursively.
	 *
	 * @param mixed &$data
	 */
	protected function injectIds( &$data ) {
		EntityTestHelper::injectIds( $data, self::$idMap );
	}

	/**
	 * Skips a test of the given entity type is not enabled.
	 *
	 * @param string $needed the required entity type
	 */
	private function skipIfEntityTypeNotKnown( $needed ) {
		if ( $needed === null ) {
			return;
		}

		$enabledTypes = WikibaseRepo::getDefaultInstance()->getEnabledEntityTypes();
		if ( !in_array( $needed, $enabledTypes ) ) {
			$this->markTestSkipped( 'Entity type not enabled: ' . $needed );
		}
	}

	/**
	 * @dataProvider provideData
	 */
	public function testEditEntity( $params, $expected, $needed = null ) {
		$this->skipIfEntityTypeNotKnown( $needed );

		$this->injectIds( $params );
		$this->injectIds( $expected );

		$p56 = '%P56%';
		$this->injectIds( $p56 );

		if ( isset( $params['data'] ) && is_array( $params['data'] ) ) {
			$params['data'] = json_encode( $params['data'] );
		}

		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		if ( !array_key_exists( 'id', $params )
			&& !array_key_exists( 'new', $params )
			&& !array_key_exists( 'site', $params )
			&& !array_key_exists( 'title', $params )
		) {
			$params['id'] = self::$idMap['!lastEntityId!'];
		}

		// -- do the request --------------------------------------------------
		list( $result, , ) = $this->doApiRequestWithToken( $params );

		// -- steal ids for later tests -------------------------------------
		if ( array_key_exists( 'new', $params ) && stristr( $params['new'], 'item' ) ) {
			self::$idMap['!lastEntityId!'] = $result['entity']['id'];
		}
		if ( array_key_exists( 'claims', $result['entity'] )
			&& array_key_exists( $p56, $result['entity']['claims'] )
		) {
			foreach ( $result['entity']['claims'][$p56] as $claim ) {
				if ( array_key_exists( 'id', $claim ) ) {
					self::$idMap['%lastClaimId%'] = $claim['id'];
				}
			}
		}

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		$this->assertArrayHasKey(
			'id',
			$result['entity'],
			"Missing 'id' section in entity in response."
		);

		$this->assertEntityEquals( $expected, $result['entity'] );

		// -- check null edits ---------------------------------------------
		if ( isset( $expected['nochange'] ) ) {
			$this->assertArrayHasKey( 'nochange', $result['entity'] );
		}

		// -- check the item in the database -------------------------------
		$dbEntity = $this->loadEntity( $result['entity']['id'] );
		$this->assertEntityEquals( $expected, $dbEntity, false );

		// -- check the edit summary --------------------------------------------
		if ( !array_key_exists( 'warning', $expected )
			|| $expected['warning'] != 'edit-no-change'
		) {
			$this->assertRevisionSummary(
				array( 'wbeditentity' ),
				$result['entity']['lastrevid']
			);

			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary(
					'/' . $params['summary'] . '/',
					$result['entity']['lastrevid']
				);
			}
		}
	}

	/**
	 * Provide data for requests that will fail with a set exception, code and message
	 * @return array
	 */
	public function provideExceptionData() {
		return array(
			'no entity id given' => array(
				'p' => array( 'id' => '', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id'
				) ) ),
			'invalid id' => array(
				'p' => array( 'id' => 'abcde', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id'
				) ) ),
			'unknown id' => array(
				'p' => array( 'id' => 'Q1234567', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity'
				) ) ),
			'invalid explicit id' => array(
				'p' => array( 'id' => '1234', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id'
				) ) ),
			'non existent sitelink' => array(
				'p' => array( 'site' => 'dewiki','title' => 'NonExistent', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-link'
				) ) ),
			'missing site (also bad title)' => array(
				'p' => array( 'title' => 'abcde', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-missing'
				) ) ),
			'cant have id and new' => array(
				'p' => array( 'id' => 'q666', 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-missing'
				) ) ),
			'when clearing must also have data!' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'clear' => '' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'nodata'
				) ) ),
			'bad site' => array(
				'p' => array( 'site' => 'abcde', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'unknown_site'
				) ) ),
			'no data provided' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'nodata' // see 'no$1' in ApiBase::$messageMap
				) )
			),
			'malformed json' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '{{{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'invalid-json'
				) ) ),
			'must be a json object (json_decode s this an an int)' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '1234' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized-array'
				) ) ),
			'must be a json object (json_decode s this an an indexed array)' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '[ "xyz" ]' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized-string'
					) ) ),
			'must be a json object (json_decode s this an a string)' => array(
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '"string"' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized-array'
				) ) ),
			'inconsistent site in json' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"ptwiki":{"site":"svwiki","title":"TestPage!"}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'inconsistent-site'
				) ) ),
			'inconsistent lang in json' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"de":{"language":"pt","value":"TestPage!"}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'inconsistent-language'
				) ) ),
			'inconsistent unknown site in json' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"BLUB":{"site":"BLUB","title":"TestPage!"}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized-site'
				) ) ),
			'inconsistent unknown languages' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"BLUB":{"language":"BLUB","value":"ImaLabel"}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized-language'
				) ) ),
			// @todo the error codes in the overly long string tests make no sense
			// and should be corrected...
			'overly long label' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"en":{"language":"en","value":"'
						. TermTestHelper::makeOverlyLongString() . '"}}}'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			'overly long description' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"descriptions":{"en":{"language":"en","value":"'
						. TermTestHelper::makeOverlyLongString() . '"}}}'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			'missing language in labels (T54731)' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"de":{"site":"pt","title":"TestString"}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'missing-language',
					'message' => '\'language\' was not found in the label or description json for de'
				) )
			),
			'removing invalid claim fails' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"claims":[{"remove":""}]}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'invalid-claim',
					'message' => 'Cannot remove a claim with no GUID'
				) )
			),
			'removing valid claim with no guid fails' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{
						"remove": "",
						"claims": [ {
							"mainsnak": {
								"snaktype": "value",
								"property": "%P56%",
								"datavalue": { "value": "imastring", "type": "string" }
							},
							"type": "statement",
							"rank": "normal"
						} ]
					}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-recognized',
					'message' => 'Unknown key in json: remove' )
				)
			),
			'bad badge id' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["abc","%Q149%"]}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'invalid-entity-id'
				) )
			),
			'badge id is not an item id' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["P2","%Q149%"]}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'invalid-entity-id'
				) )
			),
			'badge id is not specified' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["%Q149%","%Q32%"]}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-badge'
				) )
			),
			'badge item does not exist' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["Q99999","%Q149%"]}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity'
				) )
			),
			'no sitelink - cannot change badges' => array(
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"svwiki":{"site":"svwiki",'
						. '"badges":["%Q42%","%Q149%"]}}}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-sitelink'
				) )
			),
			'bad id in serialization' => array(
				'p' => array( 'id' => '%Berlin%', 'data' => '{"id":"Q13244"}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-invalid',
					'message' => 'Invalid field used in call: "id", must match id parameter'
				) )
			),
			'bad type in serialization' => array(
				'p' => array( 'id' => '%Berlin%', 'data' => '{"id":"%Berlin%","type":"foobar"}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'param-invalid',
					'message' => 'Invalid field used in call: "type", '
						. 'must match type associated with id'
				) )
			),
			'bad main snak replacement' => array(
				'p' => array( 'id' => '%Berlin%', 'data' => json_encode( array(
						'claims' => array(
							array(
								'id' => '%BerlinP56%',
								'mainsnak' => array(
									'snaktype' => 'value',
									'property' => '%P72%',
									'datavalue' => array(
										'value' => 'anotherstring',
										'type' => 'string'
									),
								),
								'type' => 'statement',
								'rank' => 'normal' ),
						),
					) ) ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'modification-failed',
					'message' => 'uses property %P56%, can\'t change to %P72%' ) ) ),
			'invalid main snak' => array(
				'p' => array( 'id' => '%Berlin%', 'data' => json_encode( array(
					'claims' => array(
						array(
							'id' => '%BerlinP56%',
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => '%P56%',
								'datavalue' => array( 'value' => '   ', 'type' => 'string' ),
							),
							'type' => 'statement',
							'rank' => 'normal' ),
					),
				) ) ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'modification-failed' ) ) ),
			'properties cannot have sitelinks' => array(
				'p' => array(
					'id' => '%P56%',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!"}}}',
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'not-supported',
					'message' => 'Non Items cannot have sitelinks'
				) ) ),
			'create mediainfo with automatic id' => array(
				'p' => array( 'new' => 'mediainfo', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => StorageException::class,
					'message' => 'mediainfo entities do not support automatic IDs'
				) ),
				't' => 'mediainfo' // skip if MediaInfo is not configured
			),
			'create mediainfo with malformed id' => array(
				'p' => array( 'id' => 'M123X', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity-id',
					'message' => 'Could not find such an entity ID'
				) ),
				't' => 'mediainfo' // skip if MediaInfo is not configured
			),
			'create mediainfo with bad id' => array(
				'p' => array( 'id' => 'M12734569', 'data' => '{}' ),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity',
					'message' => 'Could not find such an entity'
				) ),
				't' => 'mediainfo' // skip if MediaInfo is not configured
			),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testEditEntityExceptions( $params, $expected, $needed = null ) {
		$this->skipIfEntityTypeNotKnown( $needed );

		$this->injectIds( $params );
		$this->injectIds( $expected );

		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

	public function testPropertyLabelConflict() {
		$params = array(
			'action' => 'wbeditentity',
			'data' => '{
				"datatype": "string",
				"labels": { "de": { "language": "de", "value": "LabelConflict" } }
			}',
			'new' => 'property',
		);
		$this->doApiRequestWithToken( $params );

		$expectedException = array(
			'type' => UsageException::class,
			'code' => 'failed-save',
		);
		// Repeating the same request with the same label should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testItemLabelWithoutDescriptionNotConflicting() {
		$params = array(
			'action' => 'wbeditentity',
			'data' => '{ "labels": { "de": { "language": "de", "value": "NotConflicting" } } }',
			'new' => 'item',
		);
		$this->doApiRequestWithToken( $params );

		// Repeating the same request with the same label should not fail.
		list( $result, , ) = $this->doApiRequestWithToken( $params );
		$this->assertArrayHasKey( 'success', $result );
	}

	public function testItemLabelDescriptionConflict() {
		$this->markTestSkippedOnMySql();

		$params = array(
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{
				"labels": { "de": { "language": "de", "value": "LabelDescriptionConflict" } },
				"descriptions": { "de": { "language": "de", "value": "LabelDescriptionConflict" } }
			}',
		);
		$this->doApiRequestWithToken( $params );

		$expectedException = array(
			'type' => UsageException::class,
			'code' => 'modification-failed',
		);
		// Repeating the same request with the same label and description should fail.
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testClearFromBadRevId() {
		$params = array(
			'action' => 'wbeditentity',
			'id' => '%Berlin%',
			'data' => '{}',
			// 'baserevid' => '', // baserevid is set below
			'clear' => '' );
		$this->injectIds( $params );

		$setupParams = array(
			'action' => 'wbeditentity',
			'id' => $params['id'],
			'clear' => '',
			'data' => '{"descriptions":{"en":{"language":"en","value":"ClearFromBadRevidDesc1"}}}',
		);

		list( $result, , ) = $this->doApiRequestWithToken( $setupParams );
		$params['baserevid'] = $result['entity']['lastrevid'];
		$setupParams['data'] = '{"descriptions":{"en":{"language":"en","value":"ClearFromBadRevidDesc2"}}}';
		$this->doApiRequestWithToken( $setupParams );

		$expectedException = array( 'type' => UsageException::class, 'code' => 'editconflict' );
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	/**
	 * @see http://bugs.mysql.com/bug.php?id=10327
	 * @see TermSqlIndexTest::markTestSkippedOnMySql
	 */
	private function markTestSkippedOnMySql() {
		if ( $this->db->getType() === 'mysql' ) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}
	}

}
