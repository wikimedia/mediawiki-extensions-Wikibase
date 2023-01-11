'use strict';

const { action, assert, utils, wiki } = require( 'api-testing' );
const { requireExtensions } = require( './utils' );
const englishLabel = 'an-English-label-' + utils.uniq();
const englishPropertyLabel = 'an-English-Property-label-' + utils.uniq();
const propertyValue = 'a-Property-value-' + utils.uniq();

describe( 'Change dispatching', () => {
	let mindy;
	let testPropertyId;
	let testItemId;
	let testItemTitle;
	let testPageTitle;
	let sitelinkChange;

	async function dispatchChanges() {
		// apparently we need to sleep a bit before the DispatchChanges job becomes runnable :/
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
		await wiki.runAllJobs();
	}

	async function getParsedMessage( key ) {
		return ( await mindy.meta( 'allmessages', {
			ammessages: key,
			amenableparser: '1',
			formatversion: '2',
		} ) )[ 0 ].content;
	}

	before( 'require extensions', requireExtensions( [
		'WikibaseRepository',
		'WikibaseClient',
	] ) );

	before( 'set up admin', async () => {
		mindy = await action.mindy();
	} );

	before( 'create property', async () => {
		const response = await mindy.action( 'wbeditentity', {
			new: 'property',
			token: await mindy.token( 'csrf' ),
			data: JSON.stringify( {
				datatype: 'string',
				labels: {
					en: { language: 'en', value: englishPropertyLabel },
				},
			} ),
			summary: 'Create string property for ChangeDispatchTest.js',
		}, 'POST' );

		testPropertyId = response.entity.id;
	} );

	before( 'create test item', async () => {
		const response = await mindy.action( 'wbeditentity', {
			new: 'item',
			token: await mindy.token( 'csrf' ),
			data: JSON.stringify( {
				labels: {
					en: { language: 'en', value: englishLabel },
				},
				claims: [ {
					type: 'statement',
					rank: 'normal',
					mainsnak: {
						snaktype: 'value',
						property: testPropertyId,
						datavalue: { value: propertyValue, type: 'string' },
					},
				} ],
			} ),
			summary: 'Create item for ChangeDispatchTest.js',
		}, 'POST' );

		testItemId = response.entity.id;
	} );

	before( 'look up namespaces and titles', async () => {
		const namespaces = await mindy.meta( 'siteinfo', {
			siprop: 'namespaces',
			formatversion: '2',
		}, 'namespaces' );
		const itemNamespace = Object.values( namespaces )
			.find( ( namespace ) => namespace.defaultcontentmodel === 'wikibase-item' );
		assert.isOk( itemNamespace,
			'namespaces include item namespace: ' + JSON.stringify( namespaces ) );
		testItemTitle = itemNamespace.name + ':' + testItemId;
		testPageTitle = namespaces[ itemNamespace.id + 1 ].name + ':' + testItemId;
	} );

	before( 'create test page', async () => {
		await mindy.edit( testPageTitle, {
			text: `{{#statements:${testPropertyId}|from=${testItemId}}`,
			createonly: '1',
			contentmodel: 'wikitext',
			summary: `Create page using ${testItemId} for ChangeDispatchTest.js`,
		} );
	} );

	before( 'link test page to test item', async () => {
		const { siteid } = await mindy.meta( 'wikibase', { wbprop: 'siteid' } );
		await mindy.action( 'wbsetsitelink', {
			id: testItemId,
			linksite: siteid,
			linktitle: testPageTitle,
			token: await mindy.token( 'csrf' ),
			summary: 'Link page for ChangeDispatchTest.js',
		}, 'POST' );

		await dispatchChanges();
	} );

	it( 'should have sitelink change dispatched', async () => {
		const changes = await mindy.list( 'recentchanges', {
			rctype: 'external',
			rcprop: 'comment|timestamp|ids',
			rctitle: testPageTitle,
			rclimit: '2',
		} );
		assert.lengthOf( changes, 1,
			'should have exactly one external change' );
		const change = changes[ 0 ];

		const expectedMessage = await getParsedMessage( 'wikibase-comment-linked' );
		assert.propertyVal( change, 'comment', expectedMessage );
		sitelinkChange = change;
	} );

	it( 'should have sitelink usage', async () => {
		const pages = await mindy.prop( 'wbentityusage', testPageTitle );

		assert.deepNestedPropertyVal(
			pages,
			`${testPageTitle}.wbentityusage.${testItemId}.aspects`,
			[ 'S' ],
			'test page should (only) have sitelink usage for test item',
		);
	} );

	it( 'should have wikibase_item page prop', async () => {
		const pages = await mindy.prop( 'pageprops', testPageTitle, { ppprop: 'wikibase_item' } );

		assert.deepNestedPropertyVal(
			pages,
			`${testPageTitle}.pageprops.wikibase_item`,
			testItemId,
			'wikibase_item page prop should be set to linked item',
		);
	} );

	describe( 'item deletion', () => {
		before( 'delete item', async () => {
			await mindy.action( 'delete', {
				title: testItemTitle,
				token: await mindy.token( 'csrf' ),
				reason: 'Delete item for ChangeDispatchTest.js',
			}, 'POST' );

			await dispatchChanges();
		} );

		it( 'should dispatch item deletion', async () => {
			const params = {
				rctype: 'external',
				rcprop: 'comment|ids',
				rctitle: testPageTitle,
				rclimit: '3',
			};
			if ( sitelinkChange ) {
				params.rcend = sitelinkChange;
			}

			let changes = await mindy.list( 'recentchanges', params );
			if ( sitelinkChange ) {
				changes = changes.filter(
					( otherChange ) => otherChange.rcid !== sitelinkChange.rcid );
				assert.lengthOf( changes, 1,
					'should have exactly one external change other than the sitelink change' );
			} else {
				// the test for sitelinkChange did not run, so we can’t filter that change out
				// (but we can still assume that the change for item deletion is in changes[ 0 ])
				assert.lengthOf( changes, 2,
					'should have exactly two external changes, sitelink change and item deletion' );
			}
			const change = changes[ 0 ];

			const expectedMessage = await getParsedMessage( 'wikibase-comment-remove' );
			assert.propertyVal( change, 'comment', expectedMessage );
		} );

		it( 'should not have sitelink usage', async () => {
			const pages = await mindy.prop( 'wbentityusage', testPageTitle );

			assert.property( pages, testPageTitle,
				'should return data for test page' );
			const page = pages[ testPageTitle ];
			assert.notProperty( page, 'wbentityusage',
				'should not have any entity usage data' );
		} );

		it( 'should not have wikibase_item page prop', async () => {
			const pages = await mindy.prop( 'pageprops', testPageTitle, { ppprop: 'wikibase_item' } );

			assert.property( pages, testPageTitle,
				'should return data for test page' );
			const page = pages[ testPageTitle ];
			assert.notProperty( page, 'pageprops',
				'should not have any page props data' );
		} );
	} );

} );
