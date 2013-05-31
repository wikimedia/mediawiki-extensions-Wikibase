<?php

namespace Wikibase\Test;

use \Wikibase\Item;
use \Wikibase\ItemContent;

/**
 * Tests for the SpecialItemByTitle class.
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
 * @group Database
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group WikibaseEntityData
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialEntityDataTest extends SpecialPageTestBase {

	protected function saveItem( Item $item ) {
		//TODO: Same as in EntityDataRequestHandlerTest. Factor out.

		$content = ItemContent::newFromItem( $item );
		$content->save( "testing", null, EDIT_NEW );
	}

	public function getTestItem() {
		//TODO: Same as in EntityDataRequestHandlerTest. Factor out.

		static $item;

		if ( $item === null ) {
			$item = Item::newEmpty();
			$item->setLabel( 'en', 'Raarrr' );
			$this->saveItem( $item );
		}

		return $item;
	}

	protected function newSpecialPage() {
		$page = new \SpecialEntityData();
		$page->getContext()->setOutput( new \OutputPage( $page->getContext() ) );
		return $page;
	}

	public static function provideExecute() {
		$cases = EntityDataRequestHandlerTest::provideHandleRequest();

		foreach ( $cases as $n => $case ) {
			// cases with no ID given will no longer fail be show an html form

			if ( $case[0] === '' && !isset( $case[1]['id'] ) ) {
				$cases[$n][3] = '!<p>!'; // output regex //TODO: be more specific
				$cases[$n][4] = 200; // http code
				$cases[$n][5] = array(); // response headers
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider provideExecute
	 *
	 * @param string $subpage The subpage to request (or '')
	 * @param array  $params  Request parameters
	 * @param array  $headers  Request headers
	 * @param string $expRegExp   Regex to match the output against.
	 * @param int    $expCode     Expected HTTP status code
	 * @param array  $expHeaders  Expected HTTP response headers
	 */
	public function testExecute( $subpage, $params, $headers, $expRegExp, $expCode = 200, $expHeaders = array() ) {
		$item = $this->getTestItem();

		EntityDataRequestHandlerTest::injectIds( $subpage, $item );
		EntityDataRequestHandlerTest::injectIds( $params, $item );
		EntityDataRequestHandlerTest::injectIds( $headers, $item );
		EntityDataRequestHandlerTest::injectIds( $expRegExp, $item );
		EntityDataRequestHandlerTest::injectIds( $expHeaders, $item );

		$request = new \FauxRequest( $params );
		$request->response()->header( 'Status: 200 OK', true, 200 ); // init/reset

		foreach ( $headers as $name => $value ) {
			$request->setHeader( strtoupper( $name ), $value );
		}

		try {
			/* @var \FauxResponse $response */
			list( $output, $response ) = $this->executeSpecialPage( $subpage, $request );

			$this->assertEquals( $expCode, $response->getStatusCode(), "status code" );
			$this->assertRegExp( $expRegExp, $output, "output" );

			foreach ( $expHeaders as $name => $exp ) {
				$value = $response->getheader( $name );
				$this->assertNotNull( $value, "header: $name" );
				$this->assertType( 'string', $value, "header: $name" );
				$this->assertRegExp( $exp, $value, "header: $name" );
			}
		} catch ( \HttpError $e ) {
			$this->assertEquals( $expCode, $e->getStatusCode(), "status code" );
			$this->assertRegExp( $expRegExp, $e->getHTML(), "error output" );
		}
	}
}
