<?php

namespace Wikibase\Test;

/**
 * Provider class for EntityData tests.
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
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataTestProvider {

	public static function provideHandleRequest() {
		$cases = array();

		$cases[] = array( // #0: no params, fail
			'',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex //TODO: be more specific
			400,       // http code
		);

		$cases[] = array( // #1: valid item ID
			'',      // subpage
			array( 'id' => '{testitemid}', 'format' => 'json' ), // parameters
			array(), // headers
			'!^\{.*Raarrr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #2: invalid item ID
			'',      // subpage
			array( 'id' => 'Q1231231230', 'format' => 'json' ), // parameters
			array(), // headers
			'!!', // output regex
			404,  // http code
		);

		$cases[] = array( // #3: revision ID
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'revision' => '{testitemrev}',
				'format' => 'json',
			),
			array(), // headers
			'!^\{.*Raarr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #4: bad revision ID
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'revision' => '1231231230',
				'format' => 'json',
			),
			array(), // headers
			'!!', // output regex
			404,       // http code
		);

		$cases[] = array( // #5: no format, cause 303 to default format
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
			),
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!.+!'
			)
		);

		$cases[] = array( // #6: mime type
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'application/json',
			),
			array(), // headers
			'!^\{.*Raarr!', // output regex
			200,       // http code
			array( // headers
				'Content-Type' => '!^application/json(;|$)!'
			)
		);

		$cases[] = array( // #7: bad format
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'sdakljflsd',
			),
			array(), // headers
			'!!', // output regex
			415,  // http code
		);

		$cases[] = array( // #8: xml
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'xml',
			),
			array(), // headers
			'!<entity!', // output regex
			200,       // http code
			array( // headers
				'Content-Type' => '!^text/xml(;|$)!'
			)
		);

		$cases[] = array( // #9: malformed id
			'',      // subpage
			array( // parameters
				'id' => '////',
				'format' => 'json',
			),
			array(), // headers
			'!!', // output regex
			400,  // http code
		);

		// from case #0 to #9, generate #10 to #19

		$subpageCases = array();
		foreach ( $cases as $c ) {
			$case = $c;
			$case[0] = '';

			if ( isset( $case[1]['id'] ) ) {
				$case[0] .= $case[1]['id'];
				unset( $case[1]['id'] );
			}

			if ( isset( $case[1]['revision'] ) ) {
				$case[0] .= ':' . $case[1]['revision'];
				unset( $case[1]['revision'] );
			}

			if ( isset( $case[1]['format'] ) ) {
				if ( $case[4] === 200 && preg_match( '!/!', $case[1]['format'] ) ) {
					// It's a mime type, so it will trigger a redirect to the canonical form
					// when used with subpage syntax.
					$case[3] = '!!';
					$case[4] = 301;
					$case[5] = array();
				}

				$case[0] .= '.' . $case[1]['format'];
				unset( $case[1]['format'] );
			}

			$subpageCases[] = $case;
		}

		$cases = array_merge( $cases, $subpageCases );

		// add cases starting from #20

		// #20: format=application/json does not trigger a redirect
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'application/json',
			),
			array(), // headers
			'!!', // output regex
			200,  // http code
			array( // headers
				'Content-Type' => '!^application/json!'
			)
		);

		// #21: format=html does trigger a 303
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'format' => 'HTML',
			),
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!{testitemid}$!'
			)
		);

		// #22: format=html&revision=1234 does trigger a 303 to the correct rev
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => '{testitemid}',
				'revision' => '{testitemrev}',
				'format' => 'text/html',
			),
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!{testitemid}(\?|&)oldid={testitemrev}!'
			)
		);

		// #23: id=q5&format=json does not trigger a redirect
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => '{lowertestitemid}',
				'format' => 'application/json',
			),
			array(), // headers
			'!!', // output regex
			200,  // http code
			array( // headers
				'Content-Type' => '!^application/json!'
			)
		);

		// #24: /Q5 does trigger a 303
		$cases[] = array(
			'{testitemid}',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/{testitemid}\.[-./\w]+$!'
			)
		);

		// #25: /Q5.json does not trigger a redirect
		$cases[] = array(
			'{testitemid}.json',      // subpage
			array(),
			array(), // headers
			'!!', // output regex
			200,  // http code
			array( // headers
				'Content-Type' => '!^application/json!'
			)
		);

		// #26: /q5.json does trigger a 301
		$cases[] = array(
			'{lowertestitemid}.JSON',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!/{testitemid}\.json$!'
			)
		);

		// #27: /q5:1234.json does trigger a 301 to the correct rev
		$cases[] = array(
			'{lowertestitemid}:{testitemrev}.json',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!{testitemid}:{testitemrev}\.json!'
			)
		);

		// #28: /Q5.application/json does trigger a 301
		$cases[] = array(
			'{testitemid}.application/json',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!{testitemid}\.json!'
			)
		);

		// #29: /Q5.html does trigger a 303
		$cases[] = array(
			'{testitemid}.html',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!{testitemid}$!'
			)
		);

		// #30: /Q5.xyz triggers a 415
		$cases[] = array(
			'{testitemid}.xyz',      // subpage
			array(),
			array(), // headers
			'!!', // output regex
			415,  // http code
			array(), // headers
		);

		// #31: /Q5 with "Accept: text/foobar" triggers a 406
		$cases[] = array(
			'{testitemid}',      // subpage
			array(),
			array( // headers
				'Accept' => 'text/foobar'
			),
			'!!', // output regex
			406,  // http code
			array(), // headers
		);

		// #32: /Q5 with "Accept: text/html" triggers a 303
		$cases[] = array(
			'{testitemid}',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'text/HTML'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!{testitemid}$!'
			)
		);

		// #33: /Q5 with "Accept: application/json" triggers a 303
		$cases[] = array(
			'{testitemid}',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'application/foobar, application/json'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/{testitemid}.json$!'
			)
		);

		// #34: /Q5 with "Accept: text/html; q=0.5, application/json" uses weights for 303
		$cases[] = array(
			'{testitemid}',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'text/html; q=0.5, application/json'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/{testitemid}.json$!'
			)
		);
		return $cases;
	}

}
