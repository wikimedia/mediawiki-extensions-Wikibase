'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newCreateItemRequestBuilder().getRouteDescription(), () => {

	describe( '201 success response ', () => {
		it( 'can create a minimal item', async () => {
			const item = { labels: { en: `hello world ${utils.uniq()}` } };
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
			const labels = { en: `potato ${utils.uniq()}` };
			const descriptions = { en: `root vegetable ${utils.uniq()}` };
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

			const response = await newCreateItemRequestBuilder( { labels: { en: `test ${utils.uniq()}` } } )
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
			const invalidItem = { [ fieldWithInvalidValue ]: invalidValue };

			const response = await newCreateItemRequestBuilder( invalidItem ).assertInvalidRequest().makeRequest();

			const context = { path: fieldWithInvalidValue, value: invalidValue };
			assertValidError( response, 400, 'item-data-invalid-field', context );
			assert.include( response.body.message, fieldWithInvalidValue );
		} );

		it( 'invalid labels list', async () => {
			const invalidLabels = [ 'not a valid labels array' ];
			const response = await newCreateItemRequestBuilder( { labels: invalidLabels } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'item-data-invalid-field', { path: 'labels', value: invalidLabels } );
			assert.include( response.body.message, 'labels' );
		} );

		it( 'unexpected field', async () => {
			const unexpectedField = 'foo';
			const item = { [ unexpectedField ]: 'bar' };

			const response = await newCreateItemRequestBuilder( item ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'unexpected-field', { field: unexpectedField } );
			assert.strictEqual( response.body.message, 'The request body contains an unexpected field' );
		} );

		it( 'invalid label language code', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { xyz: 'label' } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-language-code',
				{ path: 'label', language: 'xyz' }
			);
			assert.include( response.body.message, 'xyz' );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newCreateItemRequestBuilder( { labels: { en: invalidLabel } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-label', { language: 'en' } );
			assert.include( response.body.message, invalidLabel );
		} );

		it( 'empty label', async () => {
			const comment = 'Empty label';
			const emptyLabel = '';
			const response = await newCreateItemRequestBuilder( { labels: { en: emptyLabel } } )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'label-empty', { language: 'en' } );
			assert.strictEqual( response.body.message, 'Label must not be empty' );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLabelLength = 250;
			const labelTooLong = 'x'.repeat( maxLabelLength + 1 );
			const comment = 'Label too long';
			const response = await newCreateItemRequestBuilder( { labels: { en: labelTooLong } } )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'label-too-long',
				{ 'character-limit': maxLabelLength, language: 'en' }
			);

			assert.strictEqual(
				response.body.message,
				`Label must be no more than ${maxLabelLength} characters long`
			);
		} );

		it( 'labels and descriptions missing', async () => {
			const response = await newCreateItemRequestBuilder( {} ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'missing-labels-and-descriptions' );
			assert.strictEqual(
				response.body.message,
				'Item requires at least a label or a description in a language'
			);
		} );

		it( 'label and description with the same value', async () => {
			const languageCode = 'en';
			const sameValueForLabelAndDescription = 'a random value';

			const itemToCreate = {};
			itemToCreate.labels = {};
			itemToCreate.descriptions = {};
			itemToCreate.labels[ languageCode ] = sameValueForLabelAndDescription;
			itemToCreate.descriptions[ languageCode ] = sameValueForLabelAndDescription;

			const response = await newCreateItemRequestBuilder( itemToCreate )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'label-description-same-value',
				{ language: languageCode }
			);
			assert.strictEqual(
				response.body.message,
				`Label and description for language '${languageCode}' can not have the same value`
			);
		} );

		it( 'item with same label and description already exists', async () => {
			const languageCode = 'en';
			const label = `test-label-${utils.uniq()}`;
			const description = `test-description-${utils.uniq()}`;

			const existingEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language: languageCode, value: label } ],
				descriptions: [ { language: languageCode, value: description } ]
			} );
			const existingItemId = existingEntityResponse.entity.id;

			const itemToCreate = {};
			itemToCreate.labels = {};
			itemToCreate.descriptions = {};
			itemToCreate.labels[ languageCode ] = label;
			itemToCreate.descriptions[ languageCode ] = description;

			const response = await newCreateItemRequestBuilder( itemToCreate )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'item-label-description-duplicate',
				{
					language: languageCode,
					label: label,
					description: description,
					'matching-item-id': existingItemId
				}
			);

			assert.strictEqual(
				response.body.message,
				`Item '${existingItemId}' already has label '${label}' associated with ` +
				`language code '${languageCode}', using the same description text`
			);
		} );
	} );
} );
