'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newCreateItemRequestBuilder().getRouteDescription(), () => {

	describe( '201 success response ', () => {
		it( 'can create a minimal item', async () => {
			const item = { labels: { en: 'hello world' } };
			const response = await newCreateItemRequestBuilder( item )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 201 );
			assert.deepEqual( response.body.labels, item.labels );
			assert.header( response, 'Location', `${response.request.url}/${response.body.id}` );

			const editMetadata = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.header( response, 'etag', makeEtag( editMetadata.revid ) );
			assert.header( response, 'last-modified', editMetadata.timestamp );
		} );

		it( 'can create an item with all fields', async () => {
			const labels = { en: 'potato' };
			const descriptions = { en: 'root vegetable' };
			const aliases = { en: [ 'spud', 'tater' ] };

			const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementValue = 'Solanum tuberosum';
			const statements = {
				[ statementPropertyId ]: [ {
					property: { id: statementPropertyId },
					value: {
						type: 'value',
						content: statementValue
					}
				} ]
			};

			const localWikiId = await entityHelper.getLocalSiteId();
			const linkedArticle = utils.title( 'Potato' );
			await entityHelper.createWikiPage( linkedArticle );
			const sitelinks = { [ localWikiId ]: { title: linkedArticle } };

			const response = await newCreateItemRequestBuilder(
				{ labels, descriptions, aliases, statements, sitelinks }
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 201 );
			assert.deepEqual( response.body.labels, labels );
			assert.deepEqual( response.body.descriptions, descriptions );
			assert.deepEqual( response.body.aliases, aliases );
			assert.strictEqual( response.body.sitelinks[ localWikiId ].title, linkedArticle );
			assert.strictEqual( response.body.statements[ statementPropertyId ][ 0 ].value.content, statementValue );
		} );

		it( 'can create an item with edit metadata provided', async () => {
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i made an edit';

			const response = await newCreateItemRequestBuilder( { labels: { en: 'test' } } )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 201 );
			const editMetadata = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				`/* wbeditentity-create-item:0| */ ${editSummary}`
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );
	} );

	describe( '400 error response ', () => {
		it( 'invalid toplevel field', async () => {
			const fieldWithInvalidValue = 'labels';
			const invalidValue = 'not an object';

			const invalidItem = {};
			invalidItem[ fieldWithInvalidValue ] = invalidValue;

			const response = await newCreateItemRequestBuilder( invalidItem )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-data-invalid-field' );
			assert.include( response.body.message, fieldWithInvalidValue );
			assert.deepEqual( response.body.context, { path: fieldWithInvalidValue, value: invalidValue } );
		} );

		it( 'invalid labels list', async () => {
			const invalidLabels = [ 'not a valid labels array' ];
			const response = await newCreateItemRequestBuilder( { labels: invalidLabels } )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'item-data-invalid-field' );
			assert.include( response.body.message, 'labels' );
			assert.deepEqual( response.body.context, { path: 'labels', value: invalidLabels } );
		} );

		it( 'unexpected field', async () => {
			const unexpectedField = 'foo';

			const item = {};
			item[ unexpectedField ] = 'bar';
			const response = await newCreateItemRequestBuilder( item )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'unexpected-field' );
			assert.strictEqual( response.body.message, 'The request body contains an unexpected field' );
			assert.deepEqual( response.body.context, { field: unexpectedField } );
		} );

		it( 'labels and descriptions missing', async () => {
			const response = await newCreateItemRequestBuilder( {} )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.header[ 'content-language' ], 'en' );
			assert.strictEqual( response.body.code, 'missing-labels-and-descriptions' );
			assert.strictEqual(
				response.body.message,
				'Item requires at least a label or a description in a language'
			);
		} );

	} );
} );
