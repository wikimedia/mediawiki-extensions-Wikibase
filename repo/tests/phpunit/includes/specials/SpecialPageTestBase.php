<?php

namespace Wikibase\Test;

/**
 * Base class for testing special pages.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SpecialPageTestBase extends \MediaWikiTestCase {

	public function testConstructor() {
		$this->assertInstanceOf( 'SpecialPage', new \SpecialItemDisambiguation() );
	}

	public function testExecute() {
		$instance = new \SpecialItemDisambiguation();
		$instance->execute( '' );

		$this->assertTrue( true, 'Calling execute without any subpage value' );

		$instance = new \SpecialItemDisambiguation();
		$instance->execute( 'en/oHai' );

		$this->assertTrue( true, 'Calling execute with a subpage value' );
	}

}
