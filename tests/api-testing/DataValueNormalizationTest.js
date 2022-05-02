'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { requireExtensions } = require( './utils.js' );

describe( 'data value normalization', () => {
	let mindy;
	let testItemId;
	let stringProperty;
	let commonsMediaProperty;

	before( 'require extensions', requireExtensions( [
		'WikibaseRepository',
	] ) );

	before( 'set up admin', async () => {
		mindy = await action.mindy();
	} );

	before( 'create test item', async () => {
		const response = await mindy.action( 'wbeditentity', {
			new: 'item',
			token: await mindy.token( 'csrf' ),
			data: JSON.stringify( {
				labels: {
					en: { language: 'en', value: 'an-English-label-' + utils.uniq() },
				},
			} ),
		}, 'POST' );
		testItemId = response.entity.id;
	} );

	before( 'create string property', async () => {
		stringProperty = await createProperty( mindy, 'string', 'string-' );
	} );

	before( 'create commonsMedia property', async () => {
		commonsMediaProperty = await createProperty( mindy, 'commonsMedia', 'commonsMedia-' );
	} );

	it( 'normalize Commons media', async function () {
		try {
			await mindy.action( 'wbcreateclaim', {
				entity: testItemId,
				snaktype: 'value',
				property: commonsMediaProperty,
				value: '"Wikidata_barcode.svg"',
				token: await mindy.token( 'csrf' ),
			}, 'POST' );
		} catch ( e ) {

			console.warn( 'Unable to save commonsMedia statement, skipping test:', e );
			return this.skip();
		}
		const entity = ( await mindy.action( 'wbgetentities', {
			ids: testItemId,
		} ) ).entities[ testItemId ];
		const value = entity.claims[ commonsMediaProperty ][ 0 ].mainsnak.datavalue.value;
		assert.equal( value, 'Wikidata barcode.svg' );
	} );

	it( 'normalize Unicode string', async () => {
		await mindy.action( 'wbcreateclaim', {
			entity: testItemId,
			snaktype: 'value',
			property: stringProperty,
			value: '"\u0061\u0301"',
			token: await mindy.token( 'csrf' ),
		}, 'POST' );
		const entity = ( await mindy.action( 'wbgetentities', {
			ids: testItemId,
		} ) ).entities[ testItemId ];
		const value = entity.claims[ stringProperty ][ 0 ].mainsnak.datavalue.value;
		assert.equal( value, '\u00e1' );
	} );

	async function createProperty( user, datatype, enLabelPrefix = '' ) {
		const response = await user.action( 'wbeditentity', {
			new: 'property',
			token: await user.token( 'csrf' ),
			data: JSON.stringify( {
				datatype,
				labels: {
					en: { language: 'en', value: enLabelPrefix + utils.uniq() },
				},
			} ),
		}, 'POST' );
		return response.entity.id;
	}

} );
