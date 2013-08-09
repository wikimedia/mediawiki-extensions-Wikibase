<?php

namespace Wikibase\Test;
use SpecialSetSiteLink;

/**
 * Tests for the SpecialSetSitelink class.
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
 * @author Adam Shorland
 */
class SpecialSetSitelinkTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialSetSitelink();
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		//TODO: Verify that item creation works via a faux post request

		$expectedInputs = array(
			'id' => array(
				'id' => 'wb-setentity-id',
				'class' => 'wb-input',
				'name' => 'id' ),
			'site' => array(
				'id' => 'wb-setsitelink-site',
				'class' => 'wb-input',
				'name' => 'site' ),
			'page' => array(
				'id' => 'wb-setsitelink-page',
				//@todo the below class does not look correct
				'class' => 'wb-input wb-input-text',
				'name' => 'page' ),
			'submit' => array(
				'id' => 'wb-setsitelink-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setsitelink-submit' ),
		);

		list( $output, ) = $this->executeSpecialPage( '' );

		foreach( $expectedInputs as $input ){
			$this->assertHasHtmlTagWithElements( $output, 'input', $input );
		}
	}

}