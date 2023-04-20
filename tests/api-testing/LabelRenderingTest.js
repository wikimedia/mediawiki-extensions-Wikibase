'use strict';

const { requireExtensions } = require( './utils' );
const { action, utils, assert } = require( 'api-testing' );

describe( 'Label Rendering in Comments', function () {
	let anonUser;
	let testItemId;
	let stringPropertyId;
	let stringPropertyLabel;

	before( 'require extensions', requireExtensions( [
		'WikibaseRepository',
	] ) );

	before( 'set up user', async () => {
		anonUser = await action.getAnon();
	} );

	before( 'create test item', async () => {
		const response = await anonUser.action( 'wbeditentity', {
			new: 'item',
			token: await anonUser.token(),
			data: JSON.stringify( {
				labels: {
					en: { language: 'en', value: 'T327062 & an-English-label-' + utils.uniq() },
				},
			} ),
		}, 'POST' );
		testItemId = response.entity.id;
	} );

	before( 'create string property', async () => {
		const stringProperty = await createProperty( anonUser, 'string', 'string-' );
		stringPropertyId = stringProperty.id;
		stringPropertyLabel = stringProperty.labels.en.value;
	} );

	it( 'renders entity labels in parsed edit summaries in API requests', async function () {
		const changeResponse = await anonUser.action( 'wbcreateclaim', {
			entity: testItemId,
			snaktype: 'value',
			property: stringPropertyId,
			value: '"some text"',
			token: await anonUser.token( 'csrf' ),
		}, 'POST' );
		const oldRevId = changeResponse.pageinfo.lastrevid;

		const queryResponse = await anonUser.action( 'query', {
			prop: 'revisions',
			formatversion: 2,
			rvprop: 'comment|parsedcomment',
			revids: oldRevId,
		}, 'GET' );

		const actualParsedComment = queryResponse.query.pages[ 0 ].revisions[ 0 ].parsedcomment;
		assert.include( actualParsedComment, stringPropertyLabel );
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
		return response.entity;
	}
} );
