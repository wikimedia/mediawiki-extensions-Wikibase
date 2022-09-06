'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { requireExtensions } = require( './utils.js' );
const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishPropertyLabel = 'an-English-Property-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();
const examplePropertyValue = 'an-example-string-' + utils.uniq();

describe( 'Lua Wikibase integration', () => {
	let mindy;
	let testPropertyId;
	let testItemId;
	let redirectedItemId;
	let module;

	before( 'require extensions', requireExtensions( [
		'Scribunto',
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
		}, 'POST' );

		testPropertyId = response.entity.id;
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
				claims: [
					{
						mainsnak: {
							snaktype: 'value',
							property: testPropertyId,
							datavalue: {
								value: examplePropertyValue,
								type: 'string',
							},
						},
						type: 'statement',
						rank: 'normal',
					},
				],
			} ),
		}, 'POST' );

		redirectedItemId = redirectedResponse.entity.id;

		await mindy.action( 'wbmergeitems', {
			token: await mindy.token( 'csrf' ),
			fromid: redirectedItemId,
			toid: testItemId,
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
				p.getEntity_claims = function( frame )
					local claims = mw.wikibase.getEntity( '${testItemId}' ).claims[ frame.args[ 1 ] ]
					if claims == nil then
						return claims
					end
					return claims[1].mainsnak.datavalue.value
				end
				p.getEntity_aliases = function() return mw.wikibase.getEntity( '${testItemId}' ).aliases.en[1].value end
				p.getEntity_labels = function() return mw.wikibase.getEntity( '${testItemId}' ).labels.de.value end
				p.getEntityLabelWithNilLang = function() return mw.wikibase.getEntity( '${testItemId}' ).labels[nil] end
				p.getEntityLabelWithObjectLang = function() return mw.wikibase.getEntity( '${testItemId}' ).labels[{}] end
				p.getEntityLabelWithIntegerLang = function() return mw.wikibase.getEntity( '${testItemId}' ).labels[2] end
				p.getEntityLabelWithInvalidLang = function() return mw.wikibase.getEntity( '${testItemId}' ).labels.INVALID end
				p.getEntityDescriptionWithNilLang = function() return mw.wikibase.getEntity( '${testItemId}' ).descriptions[nil] end
				p.getEntityDescriptionWithObjectLang = function() return mw.wikibase.getEntity( '${testItemId}' ).descriptions[{}] end
				p.getEntityDescriptionWithIntegerLang = function() return mw.wikibase.getEntity( '${testItemId}' ).descriptions[2] end
				p.getEntityDescriptionWithInvalidLang = function() return mw.wikibase.getEntity( '${testItemId}' ).descriptions.INVALID end
				p.getDescription = function() return mw.wikibase.getDescription( '${testItemId}' ) end
				p.formatItemIdValue = function( frame )
					local dataValue = { type = 'wikibase-entityid', value = { ['entity-type'] = 'item', id = frame.args[1] } }
					local snak = { datatype = 'wikibase-item', property = 'P435739845', snaktype = 'value', datavalue = dataValue }
					return mw.wikibase.formatValue( snak )
				end
				p.getLabelAfterReassignedEntityId = function()
					local entity = mw.wikibase.getEntity( '${testItemId}' )
					entity.id = 'Q2147483647'
					return entity.labels.en.value
				end

				return p
				`,
			contentmodel: 'Scribunto',
		} );
	} );

	it( 'getLabel can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabel|${testItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.match( pageText, new RegExp( englishLabel + '|' + germanLabel ) );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.isNotEmpty( usageAspects );
		for ( const usageAspect of usageAspects ) {
			assert.match( usageAspect, /^L(\..*)?$/ );
		}
	} );

	it( 'getLabel returns the label of the redirect target for a redirected item', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageForRedirectsToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabel|${redirectedItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.match( pageText, new RegExp( englishLabel + '|' + germanLabel ) );
		const usageAspects = await getUsageAspects( pageTitle, redirectedItemId );
		assert.isNotEmpty( usageAspects );
	} );

	// this test is only effective with $wgWBClientSettings['allowDataAccessInUserLanguage'] = true;
	// otherwise it still passes but doesn’t test anything in particular
	it( 'getLabel can be invoked correctly with strange uselang query param', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabel}}`, pageTitle );
		await parsePage( pageTitle, { uselang: '⧼Lang⧽' } ); // should not throw
	} );

	it( 'getLabelByLang can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabelByLang|${testItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${englishLabel}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'L.en' );
	} );

	describe( 'ensure resilience of label and description usage tracking => T287704', () => {
		/* eslint-disable mocha/no-setup-in-describe */
		[
			[ 'gets the empty label with nil as language and doesnt break addLabelUsage', 'getEntityLabelWithNilLang', null ],
			[ 'gets the empty label with an object as language and doesnt break addLabelUsage', 'getEntityLabelWithObjectLang', null ],
			[ 'gets the empty label with an integer as language and doesnt break addLabelUsage', 'getEntityLabelWithIntegerLang', null ],
			[ 'gets the empty label with an invalid language and doesnt break addLabelUsage', 'getEntityLabelWithInvalidLang', 'L' ],
			[ 'gets the empty description with nil as language and doesnt break addDescriptionUsage', 'getEntityDescriptionWithNilLang', null ],
			[ 'gets the empty description with an object as language and doesnt break addDescriptionUsage', 'getEntityDescriptionWithObjectLang', null ],
			[ 'gets the empty description with an integer as language and doesnt break addDescriptionUsage', 'getEntityDescriptionWithIntegerLang', null ],
			[ 'gets the empty description with an invalid language and doesnt break addDescriptionUsage', 'getEntityDescriptionWithInvalidLang', 'D' ],
		].forEach( ( [ testLabel, luaTestMethod, expectedAspect ] ) => {
			it( testLabel, async () => {
				const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
				await writeTextToPage( mindy, `{{#invoke:${module}|${luaTestMethod}}}`, pageTitle );
				const pageText = await parsePage( pageTitle );
				assert.equal( pageText, '' );
				const usageAspects = await getUsageAspects( pageTitle, testItemId );
				assert.equal( usageAspects, expectedAspect );
			} );
		} );
		/* eslint-enable mocha/no-setup-in-describe */
	} );

	it( 'reassigning entity ID has no impact on usage tracking', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabelAfterReassignedEntityId }}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${englishLabel}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'L.en' );
	} );

	it( 'getLabelByLang returns the label of the redirect target for a redirected item', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageForRedirectsToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getLabelByLang|${redirectedItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${englishLabel}\n</p>` );
	} );

	it( 'getEntity_claims can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getEntity_claims|${testPropertyId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${examplePropertyValue}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, `C.${testPropertyId}` );
	} );

	it( 'getEntity_claims can be invoked not yet existing property', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getEntity_claims|${testPropertyId + '1'}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, '' );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, `C.${testPropertyId + '1'}` );
	} );

	it( 'getEntity_aliases can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getEntity_aliases}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${englishLabel + '-redirected'}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'O' );
	} );

	it( 'getEntity_labels can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getEntity_labels}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${germanLabel}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.equal( usageAspects, 'L.de' );
	} );

	it( 'getDescription can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|getDescription}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.equal( pageText, `<p>${englishDescription}\n</p>` );
		const usageAspects = await getUsageAspects( pageTitle, testItemId );
		assert.isNotEmpty( usageAspects );
		for ( const usageAspect of usageAspects ) {
			assert.match( usageAspect, /^D(\..*)?$/ );
		}
	} );

	it( 'formatValue can be invoked correctly', async () => {
		const pageTitle = utils.title( 'WikibaseTestPageToParse-' );
		await writeTextToPage( mindy, `{{#invoke:${module}|formatItemIdValue|${testItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.match( pageText, new RegExp( englishLabel + '|' + germanLabel ) );
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
		await writeTextToPage( mindy, `{{#invoke:${module}|formatItemIdValue|${redirectedItemId}}}`, pageTitle );
		const pageText = await parsePage( pageTitle );
		assert.match( pageText, new RegExp( englishLabel + '|' + germanLabel ) );
		// TODO usage tracking for redirects: T280910
	} );

	function writeTextToPage( user, text, pageTitle ) {
		return user.edit( pageTitle, { text }, 'post' );
	}

	async function parsePage( pageTitle, extraParams = {} ) {
		const response = await action.getAnon().action( 'parse', {
			page: pageTitle,
			disablelimitreport: true,
			formatversion: 2,
			wrapoutputclass: '',
			...extraParams,
		} );
		return response.parse.text;
	}

	async function getUsageAspects( pageTitle, itemId ) {
		const usageResponse = await action.getAnon().action( 'query', {
			prop: 'wbentityusage',
			titles: pageTitle,
			formatversion: 2,
		} );
		if ( !usageResponse.query.pages[ 0 ].wbentityusage ) {
			// TODO: replace with optional chaning as soon as CI is on Node.js v14
			return null;
		}
		return usageResponse.query.pages[ 0 ].wbentityusage[ itemId ].aspects;
	}

} );
