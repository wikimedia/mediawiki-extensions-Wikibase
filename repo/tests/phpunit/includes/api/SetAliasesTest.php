<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for the ApiSetAliases API module.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
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
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group SetAliasesTest
 * @group BreakingTheSlownessBarrier
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SetAliasesTest extends LangAttributeTestCase {

	public function setUp() {
		self::$testAction = 'wbsetaliases';
		parent::setUp();
	}

	public static function provideData() {
		return array(
			// p => params, e => expected

			// -- Test valid sequence -----------------------------
			array( //0
				'p' => array( 'language' => 'en', 'set' => '' ),
				'e' => array( 'warning' => 'edit-no-change' ) ),
			array( //1
				'p' => array( 'language' => 'en', 'set' => 'Foo' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo' ) ) ) ),
			array( //2
				'p' => array( 'language' => 'en', 'add' => 'Foo|Bax' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bax' ) ) ) ),
			array( //3
				'p' => array( 'language' => 'en', 'set' => 'Foo|Bar|Baz' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz' ) ) ) ),
			array( //4
				'p' => array( 'language' => 'en', 'set' => 'Foo|Bar|Baz' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz' ) ), 'warning' => 'edit-no-change' ) ),
			array( //5
				'p' => array( 'language' => 'en', 'add' => 'Foo|Spam' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz', 'Spam' ) ) ) ),
			array( //6
				'p' => array( 'language' => 'en', 'add' => 'ohi' ),
				'e' => array( 'value' => array( 'en' => array( 'Foo', 'Bar', 'Baz', 'Spam', 'ohi' ) ) ) ),
			array( //7
				'p' => array( 'language' => 'en', 'set' => 'ohi' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ) ) ),
			array( //8
				'p' => array( 'language' => 'de', 'set' => '' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ), 'warning' => 'edit-no-change' ) ),
			array( //9
				'p' => array( 'language' => 'de', 'set' => 'hiya' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ), 'de' => array( 'hiya' ) ) ) ),
			array( //10
				'p' => array( 'language' => 'de', 'add' => 'opps' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ), 'de' => array( 'hiya', 'opps' ) ) ) ),
			array( //11
				'p' => array( 'language' => 'de', 'remove' => 'opps|hiya' ),
				'e' => array( 'value' => array( 'en' => array( 'ohi' ) ) ) ),
			array( //12
				'p' => array( 'language' => 'en', 'remove' => 'ohi' ),
				'e' => array(  ) ),
		);
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetLabel( $params, $expected ){
		self::doTestSetLangAttribute( 'aliases' ,$params, $expected );
	}

	public static function provideExceptionData() {
		return array(
			// p => params, e => expected

			// -- Test Exceptions -----------------------------
			array( //0
				'p' => array( 'language' => '', 'add' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_language' ) ) ),
			array( //1
				'p' => array( 'language' => 'nl', 'set' => LangAttributeTestHelper::makeOverlyLongString() ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) ) ),
			array( //2
				'p' => array( 'language' => 'pt', 'remove' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'badtoken', 'message' => 'loss of session data' ) ) ),
			array( //3
				'p' => array( 'id' => 'noANid', 'language' => 'fr', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id', 'message' => 'No entity found' ) ) ),
			array( //4
				'p' => array( 'site' => 'qwerty', 'language' => 'pl', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_site', 'message' => "Unrecognized value for parameter 'site'" ) ) ),
			array( //5
				'p' => array( 'site' => 'enwiki', 'title' => 'GhskiDYiu2nUd', 'language' => 'en', 'remove' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-link', 'message' => 'No entity found matching site link' ) ) ),
			array( //6
				'p' => array( 'title' => 'Blub', 'language' => 'en', 'add' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
			array( //7
				'p' => array( 'site' => 'enwiki', 'language' => 'en', 'set' => 'normalValue' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal', 'message' => 'Either provide the item "id" or pairs' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetLabelExceptions( $params, $expected ){
		self::doTestSetLangAttributeExceptions( $params, $expected );
	}
}