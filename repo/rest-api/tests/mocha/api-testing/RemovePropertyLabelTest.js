'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemovePropertyLabelRequestBuilder,
	newSetPropertyLabelRequestBuilder,
	newGetPropertyLabelRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newRemovePropertyLabelRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;

	before( async () => {
		testPropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;
	} );

	describe( '200 success response', () => {
		it( 'can remove a label without edit metadata', async () => {
			const languageCode = 'en';
			const label = 'english label ' + utils.uniq();
			await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, label ).makeRequest();

			const response = await newRemovePropertyLabelRequestBuilder( testPropertyId, languageCode )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Label deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetPropertyLabelRequestBuilder( testPropertyId, languageCode ).makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );
		} );

		it( 'can remove a label with metadata', async () => {
			const languageCode = 'en';
			const label = 'english label ' + utils.uniq();
			await newSetPropertyLabelRequestBuilder( testPropertyId, languageCode, label ).makeRequest();

			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'remove english label';

			const response = await newRemovePropertyLabelRequestBuilder( testPropertyId, languageCode )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Label deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetPropertyLabelRequestBuilder( testPropertyId, languageCode ).makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary( 'wbsetlabel', 'remove', languageCode, label, comment )
			);
		} );
	} );

} );
