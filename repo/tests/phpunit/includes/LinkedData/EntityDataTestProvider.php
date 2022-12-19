<?php

namespace Wikibase\Repo\Tests\LinkedData;

use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Rdf\RdfVocabulary;

/**
 * Provider class for EntityData tests.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityDataTestProvider {
	public const ITEM_REVISION_ID = 4242;
	public const PROPERTY_REVISON_ID = 4243;

	/**
	 * @return EntityRevision[]
	 */
	public static function getEntityRevisions() {
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'Raarrr!' );

		$itemRev = new EntityRevision( $item, self::ITEM_REVISION_ID, '20131211100908' );

		$property = new Property( new NumericPropertyId( 'P42' ), null, 'string' );
		$property->setLabel( 'en', 'Propertyyy' );

		$propertyRev = new EntityRevision( $property, self::PROPERTY_REVISON_ID, '20141211100908' );

		return [ $itemRev, $propertyRev ];
	}

	/**
	 * @return EntityRedirect[]
	 */
	public static function getEntityRedirects() {
		$redirect = new EntityRedirect( new ItemId( 'Q22' ), new ItemId( 'Q42' ) );

		return [ $redirect ];
	}

	public static function getMockRepository() {
		$mockRepository = new MockRepository();

		foreach ( self::getEntityRevisions() as $entityRev ) {
			$mockRepository->putEntity( $entityRev->getEntity(), $entityRev->getRevisionId(), $entityRev->getTimestamp() );
		}

		foreach ( self::getEntityRedirects() as $entityRedir ) {
			$mockRepository->putRedirect( $entityRedir );
		}

		return $mockRepository;
	}

	public static function provideHandleRequest() {
		$version = preg_quote( RdfVocabulary::FORMAT_VERSION );
		$cases = [];

		$cases[] = [ // #0: no params, fail
			'',      // subpage
			[], // parameters
			[], // headers
			'!!', // output regex //TODO: be more specific
			400,       // http code
		];

		$cases[] = [ // #1: valid item ID
			'',      // subpage
			[ 'id' => 'Q42', 'format' => 'json' ], // parameters
			[], // headers
			'!^\{.*Raarrr!s', // output regex
			200,       // http code
		];

		$cases[] = [ // #2: invalid item ID
			'',      // subpage
			[ 'id' => 'Q1231231230', 'format' => 'json' ], // parameters
			[], // headers
			'!!', // output regex
			404,  // http code
		];

		$cases[] = [ // #3: revision ID
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'revision' => '4242',
				'format' => 'json',
			],
			[], // headers
			'!^\{.*Raarr!s', // output regex
			200,       // http code
		];

		$cases[] = [ // #4: bad revision ID
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'revision' => '1231231230',
				'format' => 'json',
			],
			[], // headers
			'!!', // output regex
			500,       // http code
		];

		$cases[] = [ // #5: no format, cause 303 to default format
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
			],
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!.+!',
			],
		];

		$cases[] = [ // #6: mime type
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'format' => 'application/json',
			],
			[], // headers
			'!^\{.*Raarr!s', // output regex
			200,       // http code
			[ // headers
				'Content-Type' => '!^application/json(;|$)!',
			],
		];

		$cases[] = [ // #7: bad format
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'format' => 'sdakljflsd',
			],
			[], // headers
			'!!', // output regex
			415,  // http code
		];

		$cases[] = [ // #8: redirected id
			'',      // subpage
			[ // parameters
				'id' => 'Q22',
				'format' => 'application/json',
			],
			[], // headers
			'!^\{.*Raarr!s', // output regex
			200,       // http code
			[ // headers
				'Content-Type' => '!^application/json(;|$)!',
			],
		];

		$cases[] = [ // #9: malformed id
			'',      // subpage
			[ // parameters
				'id' => '////',
				'format' => 'json',
			],
			[], // headers
			'!!', // output regex
			400,  // http code
		];

		// from case #0 to #9, generate #10 to #19

		$subpageCases = [];
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
					$case[5] = [];
				}

				$case[0] .= '.' . $case[1]['format'];
				unset( $case[1]['format'] );
			}

			$subpageCases[] = $case;
		}

		$cases = array_merge( $cases, $subpageCases );

		// add cases starting from #20

		// #20: format=application/json does not trigger a redirect
		$cases[] = [
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'format' => 'application/json',
			],
			[], // headers
			'!!', // output regex
			200,  // http code
			[ // headers
				'Content-Type' => '!^application/json!',
			],
		];

		// #21: format=html does trigger a 303
		$cases[] = [
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'format' => 'HTML',
			],
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!Q42$!',
			],
		];

		// #22: format=html&revision=4242 does trigger a 303 to the correct rev
		$cases[] = [
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'revision' => '4242',
				'format' => 'text/html',
			],
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!Q42(\?|&)oldid=4242!',
			],
		];

		// #23: id=q42&format=json does not trigger a redirect
		$cases[] = [
			'',      // subpage
			[ // parameters
				'id' => 'q42',
				'format' => 'application/json',
			],
			[], // headers
			'!!', // output regex
			200,  // http code
			[ // headers
				'Content-Type' => '!^application/json!',
			],
		];

		// #24: /Q5 does trigger a 303
		$cases[] = [
			'Q42',      // subpage
			[], // parameters
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!/Q42\.[-./\w]+$!',
			],
		];

		// #25: /Q5.json does not trigger a redirect
		$cases[] = [
			'Q42.json',      // subpage
			[],
			[], // headers
			'!!', // output regex
			200,  // http code
			[ // headers
				'Content-Type' => '!^application/json!',
			],
		];

		// #26: /q5.json does trigger a 301
		$cases[] = [
			'q42.JSON',      // subpage
			[], // parameters
			[], // headers
			'!!', // output regex
			301,  // http code
			[ // headers
				'Location' => '!/Q42\.json$!',
			],
		];

		// #27: /q5:1234.json does trigger a 301 to the correct rev
		$cases[] = [
			'q42.json',      // subpage
			[ 'revision' => '4242' ], // parameters
			[], // headers
			'!!', // output regex
			301,  // http code
			[ // headers
				'Location' => '!Q42\.json[\?&]oldid=4242!',
			],
		];

		// #28: /Q5.application/json does trigger a 301
		$cases[] = [
			'Q42.application/json',      // subpage
			[], // parameters
			[], // headers
			'!!', // output regex
			301,  // http code
			[ // headers
				'Location' => '!Q42\.json!',
			],
		];

		// #29: /Q5.html does trigger a 303
		$cases[] = [
			'Q42.html',      // subpage
			[], // parameters
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!Q42$!',
			],
		];

		// #30: /Q5.xyz triggers a 415
		$cases[] = [
			'Q42.xyz',      // subpage
			[],
			[], // headers
			'!!', // output regex
			415,  // http code
			[], // headers
		];

		// #31: /Q5 with "Accept: text/foobar" triggers a 406
		$cases[] = [
			'Q42',      // subpage
			[],
			[ // headers
				'Accept' => 'text/foobar',
			],
			'!!', // output regex
			406,  // http code
			[], // headers
		];

		// #32: /Q5 with "Accept: text/html" triggers a 303
		$cases[] = [
			'Q42',      // subpage
			[], // parameters
			[ // headers
				'Accept' => 'text/HTML',
			],
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!Q42$!',
			],
		];

		// #33: /Q5 with "Accept: application/json" triggers a 303
		$cases[] = [
			'Q42',      // subpage
			[], // parameters
			[ // headers
				'Accept' => 'application/foobar, application/json',
			],
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!/Q42.json$!',
			],
		];

		// #34: /Q5 with "Accept: text/html; q=0.5, application/json" uses weights for 303
		$cases[] = [
			'Q42',      // subpage
			[], // parameters
			[ // headers
				'Accept' => 'text/html; q=0.5, application/json',
			],
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!/Q42.json$!',
			],
		];

		// If-Modified-Since handling

		// #35: IMS from the deep past should return a 200 (revision timestamp is 20131211100908)
		$cases[] = [
			'Q42.json',	  // subpage
			[], // parameters
			[ // headers
				'If-Modified-Since' => wfTimestamp( TS_RFC2822, '20000101000000' ),
			],
			'!!', // output regex
			200,  // http code
		];

		// #36: new IMS should return a 304 (revision timestamp is 20131211100908)
		$cases[] = [
			'Q42.json',	  // subpage
			[], // parameters
			[ // headers
				'If-Modified-Since' => '20131213141516',
			],
			'!!', // output regex
			304,  // http code
		];

		// #37: invalid, no longer supported XML format
		$cases[] = [
			'Q42.xml',
			[],
			[],
			'!!', // output regex
			415, // http code
		];

		$cases[] = [ // #38: requesting a redirect includes the followed redirect in the output
			'',      // subpage
			[ 'id' => 'Q22', 'format' => 'ntriples' ], // parameters
			[], // headers
			'!^<http://acme\.test/Q22> *<http://www\.w3\.org/2002/07/owl#sameAs> *<http://acme\.test/Q42> *.$!m', // output regex
			200,       // http code
		];

		$cases[] = [ // #39: flavors are passed on, incoming redirects are included
			'',      // subpage
			[ 'id' => 'Q42', 'format' => 'ntriples', 'flavor' => 'full' ], // parameters
			[], // headers
			'!^<http://data\.acme\.test/Q42> *'
				. "<http://schema.org/softwareVersion> *\"$version\" *\\.\$.*^"
				. '<http://acme\.test/Q22> *'
				. '<http://www\.w3\.org/2002/07/owl#sameAs> *'
				. '<http://acme\.test/Q42> *.$!sm',
			200,       // http code
		];

		$cases[] = [ // #40: dump format includes version, see T130066
			'',      // subpage
			[ 'id' => 'Q42', 'format' => 'ntriples', 'flavor' => 'dump' ], // parameters
			[], // headers
			'!^<http://data\.acme\.test/Q42> +'
				. "<http://schema.org/softwareVersion> +\"$version\" *\\.\$"
				. '!sm',
			200,       // http code
		];

		// redirect=force

		// #41: format=application/json with forced redirect
		$cases[] = [
			'',      // subpage
			[ // parameters
				'id' => 'Q42',
				'format' => 'application/json',
				'redirect' => 'force',
			],
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!/Q42\.json$!',
			],
		];

		// #42: /Q42.json with forced redirect
		$cases[] = [
			'Q42.json',      // subpage
			[ // parameters
				'redirect' => 'force',
			],
			[], // headers
			'!!', // output regex
			303,  // http code
			[ // headers
				'Location' => '!/Q42\.json!',
			],
		];

		// #43: /q42.json with forced redirect triggers a 301, not a 303
		$cases[] = [
			'q42.JSON',      // subpage
			[ // parameters
				'redirect' => 'force',
			],
			[], // headers
			'!!', // output regex
			301,  // http code
			[ // headers
				'Location' => '!/Q42\.json$!',
			],
		];

		$cases['Invalid flavor'] = [
			'',
			[ 'id' => 'Q42', 'format' => 'ntriples', 'flavor' => 'invalid' ],
			[],
			'!wikibase-entitydata-bad-flavor: invalid!',
			400,
		];

		$cases['RDF output not available for properties'] = [
			'',
			[ 'id' => 'P42', 'format' => 'rdf' ],
			[],
			'!wikibase-entitydata-rdf-not-available: property!',
			406,
		];

		$cases['TTL (RDF) output not available for properties'] = [
			'',
			[ 'id' => 'P42', 'format' => 'ttl' ],
			[],
			'!wikibase-entitydata-rdf-not-available: property!',
			406,
		];

		$cases['N3 (RDF) output not available for properties'] = [
			'',
			[ 'id' => 'P42', 'format' => 'n3' ],
			[],
			'!wikibase-entitydata-rdf-not-available: property!',
			406,
		];

		$cases['NT (RDF) output not available for properties'] = [
			'',
			[ 'id' => 'P42', 'format' => 'nt' ],
			[],
			'!wikibase-entitydata-rdf-not-available: property!',
			406,
		];

		return $cases;
	}

}
