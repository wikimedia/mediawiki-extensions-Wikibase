<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for the ApiWikibase Getentities class.
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
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group GetEntitiesTest
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
class GetEntitiesTest extends WikibaseApiTestCase {

	private static $hasSetup;
	//todo remove unused handles from this list
	private static $usedHandles = array( 'Berlin', 'London', 'Oslo' );

	public function setup() {
		parent::setup();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( self::$usedHandles );
		}
		self::$hasSetup = true;
	}

	public static function provideData() {
		return array(
			// p => params, e => expected
			array( //0 single id
				'p' => array( 'handles' => array( 'Berlin' ) ),
				'e' => array( 'count' => 1 ) ),
			array( //1 multiple ids
				'p' => array( 'handles' => array( 'London', 'Oslo' ) ),
				'e' => array( 'count' => 2 ) ),
			array( //2 multiple ids (that are the same)
				'p' => array( 'handles' => array( 'London', 'London' ) ),
				'e' => array( 'count' => 1 ) ),
			array( //3 single sitelink
				'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin' ),
				'e' => array( 'count' => 1 ) ),
			array( //4 multiple sitelink titles
				'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin|London' ),
				'e' => array( 'count' => 2 ) ),
			array( //5 multiple sitelink sites
				'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo' ),
				'e' => array( 'count' => 1 ) ),
			array( //6 multiple sitelink sites
				'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ),
				'e' => array( 'count' => 2 ) ),

			array( //7
				'p' => array( 'sites' => 'dewiki', 'titles' => 'berlin', 'normalize' => '' ),
				'e' => array( 'count' => 1, 'normalized' => true ) ),
			array( //8
				'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin', 'normalize' => '' ),
				'e' => array( 'count' => 1  ) ),

			array( //9 prop info
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'info' ),
				'e' => array( 'count' => 1 ) ),
			array( //10 prop sitelinks
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'sitelinks' ),
				'e' => array( 'count' => 1 ) ),
			array( //11 prop aliases
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'aliases' ),
				'e' => array( 'count' => 1 ) ),
			array( //12 prop labels
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'labels' ),
				'e' => array( 'count' => 1 ) ),
			array( //13 prop descriptions
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'descriptions' ),
				'e' => array( 'count' => 1 ) ),
			array( //14 prop claims
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'claims' ),
				'e' => array( 'count' => 1 ) ),
			array( //15 prop datatype
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'datatype' ),
				'e' => array( 'count' => 1 ) ),
			array( //16 prop combination
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'labels|sitelinks' ),
				'e' => array( 'count' => 1 ) ),
			array( //17 prop combination
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'claims|datatype|aliases' ),
				'e' => array( 'count' => 1 ) ),
			array( //18 prop combination
				'p' => array( 'handles' => array( 'Berlin' ), 'props' => 'info|aliases|labels|claims' ),
				'e' => array( 'count' => 1 ) ),

			array( //19 language which is present
				'p' => array( 'handles' => array( 'Berlin' ), 'languages' => 'de' ),
				'e' => array( 'count' => 1 ) ),
			array( //20 language that is not present
				'p' => array( 'handles' => array( 'Berlin' ), 'languages' => 'zh' ),
				'e' => array( 'count' => 1 ) ),
			array( //21 languages that are not present
				'p' => array( 'handles' => array( 'Berlin' ), 'languages' => 'it|es|zh|ar' ),
				'e' => array( 'count' => 1 ) ),
			array( //22 languages that are present
				'p' => array( 'handles' => array( 'Berlin' ), 'languages' => 'de|nn|no|en|en-gb' ),
				'e' => array( 'count' => 1 ) ),
			array( //23 languages that are both present and not present
				'p' => array( 'handles' => array( 'Berlin' ), 'languages' => 'de|nn|no|en|en-gb|it|es|zh|ar' ),
				'e' => array( 'count' => 1 ) ),

			array( //24
				'p' => array( 'handles' => array( 'Berlin' ), 'sort' => 'sitelinks', 'dir' => 'descending' ),
				'e' => array( 'count' => 1 ) ),
			array( //25
				'p' => array( 'handles' => array( 'Berlin' ), 'sort' => 'sitelinks', 'dir' => 'ascending' ),
				'e' => array( 'count' => 1 ) ),

		);
	}

	/**
	 * @dataProvider provideData
	 */
	function testGetEntities( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbgetentities';
		$ids = array();
		if( array_key_exists( 'handles', $params ) ){
			foreach( $params['handles'] as $handle ){
				$ids[ $handle ] = EntityTestHelper::getId( $handle );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}
		if( array_key_exists( 'props', $params ) ){
			$expected['props'] = explode( '|', $params['props'] );
		} else {
			$expected['props'] = array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'claims', 'datatype' );
		}
		if( array_key_exists( 'languages', $params ) ){
			$expected['languages'] = explode( '|', $params['languages'] );
		} else {
			$expected['languages'] = null;
		}
		if( array_key_exists( 'dir', $params ) ){
			$expected['dir'] = $params['dir'];
		} else {
			$expected['dir'] = 'ascending';
		}

		// -- do the request --------------------------------------------------
		list( $result,, ) = $this->doApiRequest( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertArrayHasKey( 'entities', $result, "Missing 'entities' section in response." );
		$this->assertEquals( $expected['count'], count( $result['entities'] ), "Request returned incorrect number of entities" );

		foreach( $result['entities'] as $entity ){
			if( in_array( 'info', $expected['props'] ) ){
				$this->assertArrayHasKey( 'pageid', $entity, 'An entity is missing the pageid value' );
				$this->assertArrayHasKey( 'ns', $entity, 'An entity is missing the ns value' );
				$this->assertArrayHasKey( 'title', $entity, 'An entity is missing the title value' );
				$this->assertArrayHasKey( 'lastrevid', $entity, 'An entity is missing the lastrevid value' );
				$this->assertArrayHasKey( 'modified', $entity, 'An entity is missing the modified value' );
				$this->assertArrayHasKey( 'id', $entity, 'An entity is missing the id value' );
				$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
			}
			if( in_array( 'datatype', $expected['props'] ) ){
				$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
			}

			$this->assertEntityEquals(
				EntityTestHelper::getEntityOutput(
					EntityTestHelper::getHandle( $entity['id'] ),
					$expected['props'],
					$expected['languages']
				),
				$entity
			);

			if( array_key_exists( 'dir', $expected ) &&
				array_key_exists( 'sitelinks', $entity ) ){

				$last = '';
				if( $expected['dir'] == 'descending' ){
					$last = 'zzzzzzzz';
				}

				foreach( $entity['sitelinks'] as $link ){
					$site = $link['site'];
					if( $expected['dir'] == 'ascending' ){
						$this->assertTrue( strcmp( $last, $site ) <= 0 , "Failed to assert order of sitelinks, ('{$last}' vs '{$site}') <=0");
					} else {
						$this->assertTrue( strcmp( $last, $site ) >= 0 , "Failed to assert order of sitelinks, ('{$last}' vs '{$site}') >=0");
					}
					$last = $site;
				}
			}
		}

		if( array_key_exists( 'normalized', $expected ) ){
			$this->assertArrayHasKey( 'normalized', $result );
			$this->assertEquals(
				$params['titles'],
				$result['normalized']['n']['from']
			);
			$this->assertEquals(
			// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
				\Title::newFromText( $params['titles'] )->getPrefixedText(),
				$result['normalized']['n']['to']
			);
		} else {
			$this->assertArrayNotHasKey( 'normalized', $result );
		}
	}

	public static function provideExceptionData() {
		//todo more exception checks should be added once Bug:53038 is resolved
		return array(
			array( //0 no params
				'p' => array( ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //1 bad id
				'p' => array( 'ids' => 'ABCD' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' ) ) ),
			array( //2 bad and good id
				'p' => array( 'ids' => 'q1|aaaa' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' ) ) ),
			array( //3 site and no title
				'p' => array( 'sites' => 'enwiki' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //4 title and no site
				'p' => array( 'titles' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //5
				'p' => array( 'ids' => 'q1', 'sites' => 'enwiki', 'titles' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testGetEntitiesExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbgetentities';
		if( array_key_exists( 'handles', $params ) ){
			$ids = array();
			foreach( $params['handles'] as $handle ){
				$ids[ $handle ] = EntityTestHelper::getId( $handle );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}

