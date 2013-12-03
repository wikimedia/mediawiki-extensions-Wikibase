<?php

namespace Wikibase\Test\Api;

/**
 * Tests for the ApiWikibaseSetDescription API module.
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
 * @group SetDescriptionTest
 * @group LanguageAttributeTest
 * @group BreakingTheSlownessBarrier
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SetDescriptionTest extends ModifyTermTestCase {

	private static $hasSetup;

	public function setUp() {
		parent::setUp();

		self::$testAction = 'wbsetdescription';

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetDescription( $params, $expected ){
		self::doTestSetTerm( 'descriptions' ,$params, $expected );
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetDescriptionExceptions( $params, $expected ){
		self::doTestSetTermExceptions( $params, $expected );
	}
}
