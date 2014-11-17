<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * Provider class for EntityData tests.
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataTestProvider {

	/**
	 * @return EntityRevision[]
	 */
	public static function getEntityRevisions() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'Raarrr!' );

		$itemRev = new EntityRevision( $item, 4242, '20131211100908' );

		return array( $itemRev );
	}

	/**
	 * @return EntityRedirect[]
	 */
	public static function getEntityRedirects() {
		$redirect = new EntityRedirect( new ItemId( 'Q22' ), new Itemid( 'Q42' ) );

		return array( $redirect );
	}

	public static function getMockRepository() {
		$mockRepository = new MockRepository();

		foreach ( self::getEntityRevisions() as $entityRev ) {
			$mockRepository->putEntity( $entityRev->getEntity(), $entityRev->getRevision(), $entityRev->getTimestamp() );
		}

		foreach ( self::getEntityRedirects() as $entityRedir ) {
			$mockRepository->putRedirect( $entityRedir );
		}

		return $mockRepository;
	}

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
			array( 'id' => 'Q42', 'format' => 'json' ), // parameters
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
				'id' => 'Q42',
				'revision' => '4242',
				'format' => 'json',
			),
			array(), // headers
			'!^\{.*Raarr!', // output regex
			200,       // http code
		);

		$cases[] = array( // #4: bad revision ID
			'',      // subpage
			array( // parameters
				'id' => 'Q42',
				'revision' => '1231231230',
				'format' => 'json',
			),
			array(), // headers
			'!!', // output regex
			500,       // http code
		);

		$cases[] = array( // #5: no format, cause 303 to default format
			'',      // subpage
			array( // parameters
				'id' => 'Q42',
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
				'id' => 'Q42',
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
				'id' => 'Q42',
				'format' => 'sdakljflsd',
			),
			array(), // headers
			'!!', // output regex
			415,  // http code
		);

		$cases[] = array( // #8: xml, redirected id
			'',      // subpage
			array( // parameters
				'id' => 'Q22',
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
				'id' => 'Q42',
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
				'id' => 'Q42',
				'format' => 'HTML',
			),
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!Q42$!'
			)
		);

		// #22: format=html&revision=1234 does trigger a 303 to the correct rev
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => 'Q42',
				'revision' => '4242',
				'format' => 'text/html',
			),
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!Q42(\?|&)oldid=4242!'
			)
		);

		// #23: id=q5&format=json does not trigger a redirect
		$cases[] = array(
			'',      // subpage
			array( // parameters
				'id' => 'q42',
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
			'Q42',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/Q42\.[-./\w]+$!'
			)
		);

		// #25: /Q5.json does not trigger a redirect
		$cases[] = array(
			'Q42.json',      // subpage
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
			'q42.JSON',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!/Q42\.json$!'
			)
		);

		// #27: /q5:1234.json does trigger a 301 to the correct rev
		$cases[] = array(
			'q42.json',      // subpage
			array( 'revision' => '4242' ), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!Q42\.json[\?&]oldid=4242!'
			)
		);

		// #28: /Q5.application/json does trigger a 301
		$cases[] = array(
			'Q42.application/json',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			301,  // http code
			array( // headers
				'Location' => '!Q42\.json!'
			)
		);

		// #29: /Q5.html does trigger a 303
		$cases[] = array(
			'Q42.html',      // subpage
			array(), // parameters
			array(), // headers
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!Q42$!'
			)
		);

		// #30: /Q5.xyz triggers a 415
		$cases[] = array(
			'Q42.xyz',      // subpage
			array(),
			array(), // headers
			'!!', // output regex
			415,  // http code
			array(), // headers
		);

		// #31: /Q5 with "Accept: text/foobar" triggers a 406
		$cases[] = array(
			'Q42',      // subpage
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
			'Q42',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'text/HTML'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!Q42$!'
			)
		);

		// #33: /Q5 with "Accept: application/json" triggers a 303
		$cases[] = array(
			'Q42',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'application/foobar, application/json'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/Q42.json$!'
			)
		);

		// #34: /Q5 with "Accept: text/html; q=0.5, application/json" uses weights for 303
		$cases[] = array(
			'Q42',      // subpage
			array(), // parameters
			array( // headers
				'Accept' => 'text/html; q=0.5, application/json'
			),
			'!!', // output regex
			303,  // http code
			array( // headers
				'Location' => '!/Q42.json$!'
			)
		);

		// If-Modified-Since handling

		// #35: IMS from the deep bast should return a 200 (revision timestamp is 20131211100908)
		$cases[] = array(
			'Q42.xml',	  // subpage
			array(), // parameters
			array( // headers
				'If-Modified-Since' => wfTimestamp( TS_RFC2822, '20000101000000' )
			),
			'!!', // output regex
			200,  // http code
		);

		// #36: new IMS should return a 304 (revision timestamp is 20131211100908)
		$cases[] = array(
			'Q42.json',	  // subpage
			array(), // parameters
			array( // headers
				'If-Modified-Since' => '20131213141516'
			),
			'!!', // output regex
			304,  // http code
		);

		return $cases;
	}

	public static function provideGetSerializedData() {
		$cases = array();

		$entityRevisions = self::getEntityRevisions();
		$entityRev = $entityRevisions[0];

		$cases[] = array( // #0: json
			'json',      // format
			$entityRev, // entityRev
			'!^\{.*Raarrr!', // output regex
			'application/json',       // expected mime
		);

		$cases[] = array( // #1: xml
			'xml',      // format
			$entityRev, // entityRev
			'!<entity!', // output regex
			'text/xml',       // expected mime
		);

		return $cases;
	}

}
