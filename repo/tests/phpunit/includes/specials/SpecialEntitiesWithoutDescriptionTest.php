<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialEntitiesWithoutDescription class.
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
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Adam Shorland
 */
class SpecialEntitiesWithoutDescriptionTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \SpecialEntitiesWithoutDescription();
	}

	public function testExecute() {

		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-entitieswithoutpage-language',
				'name' => 'language',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-entitieswithoutpage-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );


		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'en' );
		$this->assertTrue( true, 'Calling execute with a subpage value' ); //TODO: assert output
	}

}