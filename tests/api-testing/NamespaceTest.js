'use strict';

const { assert, action } = require( 'api-testing' );

describe( 'namespaces', () => {

	before( 'require extensions', async function () {
		const requiredExtensions = [
			'WikibaseRepository',
		];
		const installedExtensions = ( await action.getAnon().meta(
			'siteinfo',
			{ siprop: 'extensions' },
			'extensions',
		) ).map( ( extension ) => extension.name );
		const missingExtensions = requiredExtensions.filter(
			( requiredExtension ) => installedExtensions.indexOf( requiredExtension ) === -1,
		);
		if ( missingExtensions.length ) {
			this.skip();
		}
	} );

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
