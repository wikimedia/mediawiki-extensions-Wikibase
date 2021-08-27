'use strict';

const { assert, action } = require( 'api-testing' );
const { requireExtensions } = require( './utils.js' );

describe( 'namespaces', () => {

	before( 'require extensions', requireExtensions( [
		'WikibaseRepository',
	] ) );

	const testCases = [
		[ 120, 'wikibase-item' ],
		[ 122, 'wikibase-property' ],
	];
	for ( const [ namespace, contentmodel ] of testCases ) {

		it( `${namespace} has defaultcontentmodel ${contentmodel}`, async () => {
			const namespaces = await action.getAnon().meta(
				'siteinfo',
				{ siprop: 'namespaces' },
				'namespaces',
			);
			assert.strictEqual( namespaces[ namespace ].defaultcontentmodel, contentmodel );
		} );

	}

} );
