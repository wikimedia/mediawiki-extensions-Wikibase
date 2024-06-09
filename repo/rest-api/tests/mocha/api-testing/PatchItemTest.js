'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const { newPatchItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatWholeEntityEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newPatchItemRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const testEnglishLabel = `some-label-${utils.uniq()}`;
	const languageWithExistingLabel = 'en';
	const languageWithExistingDescription = 'en';

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: languageWithExistingLabel, value: testEnglishLabel } ],
			descriptions: [ { language: languageWithExistingDescription, value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		predicatePropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {

		it( 'can patch an item', async () => {
			const newLabel = `neues deutsches label ${utils.uniq()}`;
			const updatedDescription = `changed description ${utils.uniq()}`;
			const newStatementValue = 'new statement';
			const editSummary = 'I made a patch';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[
					{ op: 'add', path: '/labels/de', value: newLabel },
					{ op: 'replace', path: '/descriptions/en', value: updatedDescription },
					{ op: 'remove', path: '/aliases/fr' },
					{
						op: 'add',
						path: `/statements/${predicatePropertyId}`,
						value: [ {
							property: { id: predicatePropertyId },
							value: { type: 'value', content: newStatementValue }
						} ]
					}
				]
			).withJsonBodyParam( 'comment', editSummary ).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.id, testItemId );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				new RegExp( `^${testItemId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				editMetadata.comment,
				formatWholeEntityEditSummary( 'update-languages-and-other-short', 'de, en, fr', editSummary )
			);
		} );

	} );

	describe( '400 error response ', () => {

		it( 'item ID is invalid', async () => {
			const itemId = 'X123';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-item-id' );
			assert.include( response.body.message, itemId );
		} );

		testValidatesPatch( ( patch ) => newPatchItemRequestBuilder( testItemId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchItemRequestBuilder( testItemId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q99999';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'item-not-found' );
			assert.include( response.body.message, itemId );
		} );
	} );

	describe( '409 error response', () => {

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnglishLabel } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnglishLabel );
		} );

		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newPatchItemRequestBuilder( redirectSource, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item' );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );

	describe( '422 error response', () => {
		it( 'invalid operation change item id', async () => {
			const patch = [
				{ op: 'replace', path: '/id', value: 'Q123' }
			];

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-item-invalid-operation-change-item-id' );
			assert.strictEqual( response.body.message, 'Cannot change the ID of the existing item' );
		} );

		it( 'unexpected field', async () => {
			const patch = [ { op: 'add', path: '/foo', value: 'bar' } ];
			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-item-unexpected-field' );
			assert.strictEqual( response.body.message, "The patched item contains an unexpected field: 'foo'" );
		} );

		const makeReplaceExistingLabelPatchOp = ( newLabel ) => ( {
			op: 'replace',
			path: `/labels/${languageWithExistingLabel}`,
			value: newLabel
		} );

		it( 'invalid labels type', async () => {
			const invalidLabels = [ 'not', 'an', 'object' ];
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '/labels', value: invalidLabels } ]
			).assertValidRequest().makeRequest();

			const context = { path: 'labels', value: invalidLabels };
			assertValidError( response, 422, 'patched-item-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'labels' in the patched item" );
		} );

		it( 'invalid label', async () => {
			const language = languageWithExistingLabel;
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-label-invalid', { language, value: invalidLabel } );
			assert.include( response.body.message, invalidLabel );
			assert.include( response.body.message, `'${language}'` );
		} );

		it( 'invalid label type', async () => {
			const language = languageWithExistingLabel;
			const label = { object: 'not allowed' };
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( label ) ]
			).assertValidRequest().makeRequest();

			const context = { language, value: JSON.stringify( label ) };
			assertValidError( response, 422, 'patched-label-invalid', context );
			assert.include( response.body.message, JSON.stringify( label ) );
			assert.include( response.body.message, `'${languageWithExistingLabel}'` );
		} );

		it( 'empty label', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-label-empty', { language: languageWithExistingLabel } );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongLabel = 'x'.repeat( maxLength + 1 );
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( tooLongLabel ) ]
			).assertValidRequest().makeRequest();

			const context = { value: tooLongLabel, 'character-limit': maxLength, language: languageWithExistingLabel };
			assertValidError( response, 422, 'patched-label-too-long', context );
			assert.strictEqual(
				response.body.message,
				`Changed label for '${languageWithExistingLabel}' must not be more than ${maxLength} characters long`
			);
		} );

		it( 'invalid label language code', async () => {
			const language = 'invalid-language-code';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ {
					op: 'add',
					path: `/labels/${language}`,
					value: 'potato'
				} ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-labels-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		const makeReplaceExistingDescriptionPatchOperation = ( newDescription ) => ( {
			op: 'replace',
			path: `/descriptions/${languageWithExistingDescription}`,
			value: newDescription
		} );

		it( 'invalid descriptions type', async () => {
			const invalidDescriptions = [ 'not', 'an', 'object' ];
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'replace', path: '/descriptions', value: invalidDescriptions } ]
			).assertValidRequest().makeRequest();

			const context = { path: 'descriptions', value: invalidDescriptions };
			assertValidError( response, 422, 'patched-item-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'descriptions' in the patched item" );
		} );

		it( 'invalid description', async () => {
			const language = 'en';
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-invalid', { language, value: invalidDescription } );
			assert.include( response.body.message, invalidDescription );
			assert.include( response.body.message, `'${language}'` );
		} );

		it( 'invalid description type', async () => {
			const invalidDescription = { object: 'not allowed' };
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { language: 'en', value: JSON.stringify( invalidDescription ) };
			assertValidError( response, 422, 'patched-description-invalid', context );
			assert.include( response.body.message, JSON.stringify( invalidDescription ) );
			assert.include( response.body.message, "'en'" );
		} );

		it( 'empty description', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongDescription = 'x'.repeat( maxLength + 1 );
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( tooLongDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { value: tooLongDescription, 'character-limit': maxLength, language: 'en' };
			assertValidError( response, 422, 'patched-description-too-long', context );
			assert.strictEqual(
				response.body.message,
				`Changed description for 'en' must not be more than ${maxLength} characters long`
			);
		} );

		it( 'invalid description language code', async () => {
			const language = 'invalid-language-code';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: `/descriptions/${language}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-descriptions-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'label and description with the same value', async () => {
			const sameValueForLabelAndDescription = 'a random value';

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[
					makeReplaceExistingLabelPatchOp( sameValueForLabelAndDescription ),
					makeReplaceExistingDescriptionPatchOperation( sameValueForLabelAndDescription )
				]
			).assertValidRequest().makeRequest();

			const context = { language: languageWithExistingLabel };
			assertValidError( response, 422, 'patched-item-label-description-same-value', context );

			assert.strictEqual(
				response.body.message,
				`Label and description for language code '${languageWithExistingLabel}' can not have the same value`
			);
		} );

		it( 'item with same label and description already exists', async () => {
			const label = `test-label-${utils.uniq()}`;
			const description = `test-description-${utils.uniq()}`;

			const existingEntityResponse = await entityHelper.createEntity( 'item', {
				labels: [ { language: languageWithExistingLabel, value: label } ],
				descriptions: [ { language: languageWithExistingDescription, value: description } ]
			} );

			const existingItemId = existingEntityResponse.entity.id;
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[
					makeReplaceExistingLabelPatchOp( label ),
					makeReplaceExistingDescriptionPatchOperation( description )
				]
			).assertValidRequest().makeRequest();

			const context = {
				language: languageWithExistingLabel,
				label,
				description,
				'matching-item-id': existingItemId
			};
			assertValidError( response, 422, 'patched-item-label-description-duplicate', context );
			assert.strictEqual(
				response.body.message,
				`Item '${existingItemId}' already has label '${label}' associated with language code ` +
				`'${languageWithExistingLabel}', using the same description text`
			);
		} );
	} );

} );
