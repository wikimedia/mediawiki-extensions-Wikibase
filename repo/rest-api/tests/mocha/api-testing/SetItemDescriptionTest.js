'use strict';

const { assert, utils } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newSetItemDescriptionRequestBuilder().getRouteDescription(), () => {
	let testItemId;

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ]
		} );
		testItemId = createEntityResponse.entity.id;
	} );

	it( 'can replace a description', async () => {
		const description = 'new description';
		const languageCode = 'en';
		const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body, description );
	} );

} );
