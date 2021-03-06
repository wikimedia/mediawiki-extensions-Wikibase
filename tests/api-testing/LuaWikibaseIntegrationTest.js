'use strict';

const { assert, action, utils } = require( 'api-testing' );
const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'Lua Wikibase integration', () => {
	let mindy;
	let testItemId;
	let redirectedItemId;
	let module;

	before( 'require extensions', async function () {
		const requiredExtensions = [
			'Scribunto',
			'WikibaseRepository',
			'WikibaseClient',
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

	before( 'set up admin', async () => {
		mindy = await action.mindy();
	} );

	before( 'create test item', async () => {
		const response = await mindy.action( 'wbeditentity', {
			new: 'item',
			token: await mindy.token( 'csrf' ),
			data: JSON.stringify( {
				labels: {
					de: { language: 'de', value: germanLabel },
					en: { language: 'en', value: englishLabel },
				},
				descriptions: {
					en: { language: 'en', value: englishDescription },
				},
			} ),
		}, 'POST' );
		testItemId = response.entity.id;
	} );

	before( 'create a second test item and merge the two to create a redirect', async () => {
		const redirectedResponse = await mindy.action( 'wbeditentity', {
			new: 'item',
			token: await mindy.token( 'csrf' ),
			data: JSON.stringify( {
				labels: {
					de: { language: 'de', value: germanLabel + '-redirected' },
					en: { language: 'en', value: englishLabel + '-redirected' },
				},
				descriptions: {
					en: { language: 'en', value: englishDescription },
				},
			} ),
		}, 'POST' );

		redirectedItemId = redirectedResponse.entity.id;

		await mindy.action( 'wbmergeitems', {
			token: await mindy.token( 'csrf' ),
			fromid: redirectedItemId,
			toid: testItemId,
			ignoreconflicts: [ 'description', 'sitelink', 'statement' ],
			summary: 'Merge the items to test redirects',
		}, 'POST' );

	} );

	before( 'create test module', async () => {
		module = utils.title( 'LuaWikibaseIntegrationTest-' );
		await mindy.edit( `Module:${module}`, {
			text: `
				local p = {}
				p.getLabel = function( frame ) return mw.wikibase.getLabel( frame.args[ 1 ] ) end
				p.getLabelByLang = function( frame ) return mw.wikibase.getLabelByLang( frame.args[ 1 ], 'en' ) end
				p.getEntity_labels = function() return mw.wikibase.getEntity( '${testItemId}' ).labels.de.value end
				p.getDescription = function() return mw.wikibase.getDescription( '${testItemId}' ) end
				p.formatItemIdValue = function( frame )
					local dataValue = { type = 'wikibase-entityid', value = { ['entity-type'] = 'item', id = frame.args[1] } }
					local snak = { datatype = 'wikibase-item', property = 'P435739845', snaktype = 'value', datavalue = dataValue }
					return mw.wikibase.formatValue( snak )
				end
				return p
				`,
			contentmodel: 'Scribunto',
		} );
	} );

	it( 'getLabel can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getLabel|${testItemId}}}`, pageTitle );
		const pageResponse = await parsePage( pageTitle );
		assert.match( pageResponse.parse.text, new RegExp( englishLabel + '|' + germanLabel ) );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.isNotEmpty( usageAspects );
		for ( const usageAspect of usageAspects ) {
			assert.match( usageAspect, /^L(\..*)?$/ );
		}
	} );

	it( 'getLabel returns the label of the redirect target for a redirected item', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageForRedirectsToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getLabel|${redirectedItemId}}}`, pageTitle );
		const pageResponse = await parsePage( pageTitle );
		assert.match( pageResponse.parse.text, new RegExp( englishLabel + '|' + germanLabel ) );
		const usageAspects = await getUsageAspects( pageTitle, redirectedItemId );
		assert.isNotEmpty( usageAspects );
	} );

	// this test is only effective with $wgWBClientSettings['allowDataAccessInUserLanguage'] = true;
	// otherwise it still passes but doesn’t test anything in particular
	it( 'getLabel can be invoked correctly with strange uselang query param', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getLabel}}`, pageTitle );
		await parsePage( pageTitle, { uselang: '⧼Lang⧽' } ); // should not throw
	} );

	it( 'getLabelByLang can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getLabelByLang|${testItemId}}}`, pageTitle );
		const pageResponse = await parsePage( pageTitle );
		assert.equal( pageResponse.parse.text, `<p>${englishLabel}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'L.en' );
	} );

	it( 'getLabelByLang returns the label of the redirect target for a redirected item', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageForRedirectsToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getLabelByLang|${redirectedItemId}}}`, pageTitle );
		const pageResponse = await parsePage( pageTitle );
		assert.equal( pageResponse.parse.text, `<p>${englishLabel}\n</p>` );
	} );

	it( 'getEntity_labels can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getEntity_labels}}`, pageTitle );
		const response = await parsePage( pageTitle );
		assert.equal( response.parse.text, `<p>${germanLabel}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'L.de' );
	} );

	it( 'getDescription can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|getDescription}}`, pageTitle );
		const response = await parsePage( pageTitle );
		assert.equal( response.parse.text, `<p>${englishDescription}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.isNotEmpty( usageAspects );
		for ( const usageAspect of usageAspects ) {
			assert.match( usageAspect, /^D(\..*)?$/ );
		}
	} );

	it( 'formatValue can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|formatItemIdValue|${testItemId}}}`, pageTitle );
		const response = await parsePage( pageTitle );
		assert.match( response.parse.text, new RegExp( englishLabel + '|' + germanLabel ) );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.include( usageAspects, 'T' );
		const otherUsageAspects = usageAspects.filter( ( usageAspect ) => usageAspect !== 'T' );
		assert.isNotEmpty( otherUsageAspects );
		for ( const usageAspect of otherUsageAspects ) {
			assert.match( usageAspect, /^L(\..*)?$/ );
		}
	} );

	it( 'formatValue uses the label of the redirect target for a redirected item', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( `{{#invoke:${module}|formatItemIdValue|${redirectedItemId}}}`, pageTitle );
		const response = await parsePage( pageTitle );
		assert.match( response.parse.text, new RegExp( englishLabel + '|' + germanLabel ) );
		// TODO usage tracking for redirects: T280910
	} );

	function writeTextToPage( text, pageTitle ) {
		return action.getAnon().edit( pageTitle, { text }, 'post' );
	}

	function parsePage( pageTitle, extraParams = {} ) {
		return action.getAnon().action( 'parse', {
			page: pageTitle,
			disablelimitreport: true,
			formatversion: 2,
			wrapoutputclass: '',
			...extraParams,
		} );
	}

	async function getUsageAspects( pageTitle, itemId ) {
		const usageResponse = await action.getAnon().action( 'query', {
			prop: 'wbentityusage',
			titles: pageTitle,
			indexpageids: true,
		} );
		const pageId = usageResponse.query.pageids[ 0 ];
		return usageResponse.query.pages[ pageId ].wbentityusage[ itemId ].aspects;
	}

} );
