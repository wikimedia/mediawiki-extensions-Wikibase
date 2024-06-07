'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const {
	newPatchPropertyRequestBuilder,
	newAddPropertyStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { formatWholeEntityEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newPatchPropertyRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const testEnglishLabel = `some-label-${utils.uniq()}`;
	const languageWithExistingLabel = 'en';
	const languageWithExistingDescription = 'en';

	before( async function () {
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: languageWithExistingLabel, value: testEnglishLabel } ],
			descriptions: [ { language: languageWithExistingDescription, value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		predicatePropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {

		it( 'can patch a property', async () => {
			const newLabel = `neues deutsches label ${utils.uniq()}`;
			const updatedDescription = `changed description ${utils.uniq()}`;
			const newStatementValue = 'new statement';
			const editSummary = 'I made a patch';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
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
			assert.strictEqual( response.body.id, testPropertyId );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				new RegExp( `^${testPropertyId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.strictEqual(
				editMetadata.comment,
				formatWholeEntityEditSummary( 'update-languages-and-other-short', 'de, en, fr', editSummary )
			);
		} );

	} );

	describe( '400 Bad Request', () => {

		it( 'property ID is invalid', async () => {
			const propertyId = 'X123';
			const response = await newPatchPropertyRequestBuilder( propertyId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-property-id', { 'property-id': propertyId } );
			assert.include( response.body.message, propertyId );
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyRequestBuilder( testPropertyId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );

	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P99999';
			const response = await newPatchPropertyRequestBuilder( propertyId, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

	} );

	describe( '409 error response', () => {

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnglishLabel } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnglishLabel );
		} );

	} );

	describe( '422 error response', () => {
		it( 'invalid operation change property id', async () => {
			const patch = [
				{ op: 'replace', path: '/id', value: 'P666' }
			];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-property-invalid-operation-change-property-id' );
			assert.strictEqual( response.body.message, 'Cannot change the ID of the existing property' );
		} );

		it( 'invalid operation change property datatype', async () => {
			const patch = [
				{ op: 'replace', path: '/data-type', value: 'wikibase-item' }
			];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-property-invalid-operation-change-property-datatype' );
			assert.strictEqual( response.body.message, 'Cannot change the datatype of the existing property' );
		} );

		it( 'missing mandatory field', async () => {
			const patch = [ { op: 'remove', path: '/data-type' } ];
			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-property-missing-field' );
		} );

		it( 'unexpected field', async () => {
			const patch = [ { op: 'add', path: '/foo', value: 'bar' } ];
			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			expect( response ).to.have.status( 422 );
			assert.strictEqual( response.body.code, 'patched-property-unexpected-field' );
		} );

		const makeReplaceExistingLabelPatchOp = ( newLabel ) => ( {
			op: 'replace',
			path: `/labels/${languageWithExistingLabel}`,
			value: newLabel
		} );

		it( 'invalid labels type', async () => {
			const invalidLabels = [ 'not', 'an', 'object' ];
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ { op: 'replace', path: '/labels', value: invalidLabels } ]
			).assertValidRequest().makeRequest();

			const context = { path: 'labels', value: invalidLabels };
			assertValidError( response, 422, 'patched-property-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'labels' in the patched property" );
		} );

		it( 'invalid label', async () => {
			const language = languageWithExistingLabel;
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-label-invalid', { language, value: invalidLabel } );
			assert.include( response.body.message, invalidLabel );
			assert.include( response.body.message, `'${language}'` );
		} );

		it( 'invalid label type', async () => {
			const language = languageWithExistingLabel;
			const label = { object: 'not allowed' };
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( label ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-label-invalid', { language, value: JSON.stringify( label ) } );
			assert.include( response.body.message, JSON.stringify( label ) );
			assert.include( response.body.message, `'${languageWithExistingLabel}'` );
		} );

		it( 'empty label', async () => {
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-label-empty', { language: languageWithExistingLabel } );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongLabel = 'x'.repeat( maxLength + 1 );
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
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
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ {
					op: 'add',
					path: `/labels/${language}`,
					value: 'potato'
				} ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-labels-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'property with same label already exists', async () => {
			const label = `test-label-${utils.uniq()}`;

			const existingEntityResponse = await entityHelper.createEntity( 'property', {
				labels: [ { language: languageWithExistingLabel, value: label } ],
				datatype: 'string'
			} );
			const existingPropertyId = existingEntityResponse.entity.id;
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( label ) ]
			).assertValidRequest().makeRequest();

			const context = { language: languageWithExistingLabel, label, 'matching-property-id': existingPropertyId };
			assertValidError( response, 422, 'patched-property-label-duplicate', context );

			assert.strictEqual(
				response.body.message,
				`Property ${existingPropertyId} already has label '${label}' associated with ` +
				`language code '${languageWithExistingLabel}'`
			);
		} );

		const makeReplaceExistingDescriptionPatchOperation = ( newDescription ) => ( {
			op: 'replace',
			path: `/descriptions/${languageWithExistingDescription}`,
			value: newDescription
		} );

		it( 'invalid descriptions type', async () => {
			const invalidDescriptions = [ 'not', 'an', 'object' ];
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ { op: 'replace', path: '/descriptions', value: invalidDescriptions } ]
			).assertValidRequest().makeRequest();

			const context = { path: 'descriptions', value: invalidDescriptions };
			assertValidError( response, 422, 'patched-property-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'descriptions' in the patched property" );
		} );

		it( 'invalid description', async () => {
			const language = 'en';
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-invalid', { language, value: invalidDescription } );
			assert.include( response.body.message, invalidDescription );
			assert.include( response.body.message, `'${language}'` );
		} );

		it( 'invalid description type', async () => {
			const invalidDescription = { object: 'not allowed' };
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-description-invalid',
				{ language: 'en', value: JSON.stringify( invalidDescription ) }
			);
			assert.include( response.body.message, JSON.stringify( invalidDescription ) );
			assert.include( response.body.message, "'en'" );
		} );

		it( 'empty description', async () => {
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-description-empty', { language: 'en' } );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongDescription = 'x'.repeat( maxLength + 1 );
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( tooLongDescription ) ]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-description-too-long',
				{ value: tooLongDescription, 'character-limit': maxLength, language: 'en' }
			);
			assert.strictEqual(
				response.body.message,
				`Changed description for 'en' must not be more than ${maxLength} characters long`
			);
		} );

		it( 'invalid description language code', async () => {
			const language = 'invalid-language-code';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: `/descriptions/${language}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-descriptions-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'patched-property-label-description-same-value', async () => {
			const language = languageWithExistingLabel;
			const text = `label-and-description-text-${utils.uniq()}`;

			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[
					{ op: 'replace', path: '/labels/en', value: text },
					{ op: 'replace', path: '/descriptions/en', value: text }
				]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-property-label-description-same-value', { language } );
			assert.strictEqual(
				response.body.message,
				`Label and description for language code ${language} can not have the same value.`
			);
		} );

		it( 'empty alias', async () => {
			const language = 'de';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ '' ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-alias-empty', { language } );
			assert.include( response.body.message, language );
		} );

		it( 'alias too long', async () => {
			const language = 'de';
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const tooLongAlias = 'x'.repeat( maxLength + 1 );
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ tooLongAlias ] }
			] ).assertValidRequest().makeRequest();

			const context = { language, value: tooLongAlias, 'character-limit': maxLength };
			assertValidError( response, 422, 'patched-alias-too-long', context );
			assert.include( response.body.message, language );
		} );

		it( 'duplicate alias', async () => {
			const language = 'en';
			const duplicate = 'tomato';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ duplicate, duplicate ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-duplicate-alias', { language, value: duplicate } );
			assert.include( response.body.message, language );
			assert.include( response.body.message, duplicate );
		} );

		it( 'aliases in language not a list', async () => {
			const language = 'en';
			const invalidAliasesValue = { 'aliases in language': 'not a list' };
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: invalidAliasesValue }
			] ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-aliases-invalid-field',
				{ path: language, value: invalidAliasesValue }
			);
		} );

		it( 'aliases is not an object', async () => {
			const invalidAliases = [ 'not', 'an', 'object' ];
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: '/aliases', value: invalidAliases }
			] ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-property-invalid-field',
				{ path: 'aliases', value: invalidAliases }
			);
		} );

		it( 'alias contains invalid characters', async () => {
			const language = 'en';
			const invalidAlias = 'tab\t tab\t tab';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ invalidAlias ] }
			] ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patched-aliases-invalid-field',
				{ path: `${language}/0`, value: invalidAlias }
			);
			assert.include( response.body.message, language );
		} );

		it( 'invalid language code', async () => {
			const language = 'not-a-valid-language';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ 'alias' ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-aliases-invalid-language-code', { language } );
			assert.include( response.body.message, language );
		} );

		function makeStatementPatchOperation( propertyId, invalidStatement ) {
			return [ {
				op: 'add',
				path: '/statements',
				value: { [ propertyId ]: [ invalidStatement ] }
			} ];
		}

		it( 'invalid statement group type', async () => {
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const invalidStatementGroupType = { [ predicatePropertyId ]: validStatement };

			const patch = [ { op: 'add', path: '/statements', value: invalidStatementGroupType } ];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-invalid-statement-group-type', { path: predicatePropertyId } );
			assert.strictEqual( response.body.message, 'Not a valid statement group' );

		} );

		it( 'invalid statement type', async () => {
			const invalidStatement = [ {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			} ];
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-invalid-statement-type', { path: `${predicatePropertyId}/0` } );
			assert.strictEqual( response.body.message, 'Not a valid statement type' );
		} );

		it( 'invalid statements type', async () => {
			const invalidStatements = [ 'invalid statements type' ];
			const patch = [ { op: 'add', path: '/statements', value: invalidStatements } ];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: 'statements', value: invalidStatements };
			assertValidError( response, 422, 'patched-property-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'statements' in the patched property" );
		} );

		it( 'invalid statement field', async () => {
			const invalidRankValue = 'invalid rank';
			const invalidStatement = { rank: invalidRankValue };
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: 'rank', value: invalidRankValue };
			assertValidError( response, 422, 'patched-statement-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for 'rank' in the patched statement" );
		} );

		it( 'missing statement field', async () => {
			const invalidStatement = { value: { type: 'somevalue', content: 'some-content' } };
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: 'property' };
			assertValidError( response, 422, 'patched-statement-missing-field', context );
			assert.strictEqual(
				response.body.message,
				'Mandatory field missing in the patched statement: property'
			);
		} );

		it( 'statement property id mismatch', async () => {
			const propertyIdKey = 'P123';
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const patch = makeStatementPatchOperation( propertyIdKey, validStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = {
				path: `${propertyIdKey}/0/property/id`,
				'statement-group-property-id': propertyIdKey,
				'statement-property-id': predicatePropertyId
			};
			assertValidError( response, 422, 'patched-statement-group-property-id-mismatch', context );
			assert.strictEqual(
				response.body.message,
				"Statement's Property ID does not match the statement group key"
			);
		} );

		it( 'statement IDs not modifiable or provided for new statements', async () => {
			const invalidStatement = {
				id: 'P123$4YY2B0D8-BEC1-4D30-B88E-347E08AFD987',
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { 'statement-id': invalidStatement.id };
			assertValidError( response, 422, 'statement-id-not-modifiable', context );
			assert.strictEqual( response.body.message, 'Statement IDs cannot be created or modified' );
		} );

		it( 'duplicate Statement id', async () => {
			const duplicateStatement = {
				id: 'P123$4YY2B0D8-BEC1-4D30-B88E-347E08AFD987',
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const invalidStatementGroup = [ duplicateStatement, duplicateStatement ];
			const patch = [ {
				op: 'add',
				path: '/statements',
				value: { [ predicatePropertyId ]: invalidStatementGroup }
			} ];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { 'statement-id': duplicateStatement.id };
			assertValidError( response, 422, 'statement-id-not-modifiable', context );
			assert.strictEqual( response.body.message, 'Statement IDs cannot be created or modified' );
		} );

		it( 'property IDs modified', async () => {
			const newPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const existingStatementsId = ( await newAddPropertyStatementRequestBuilder( testPropertyId, {
				property: { id: predicatePropertyId },
				value: { type: 'novalue' }
			} ).makeRequest() ).body.id;
			const invalidStatement = {
				id: existingStatementsId,
				property: { id: newPropertyId },
				value: { type: 'value', content: 'some-value' }
			};

			const patch = makeStatementPatchOperation( newPropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { 'statement-id': existingStatementsId, 'statement-property-id': predicatePropertyId };
			assertValidError( response, 422, 'patched-statement-property-not-modifiable', context );
			assert.strictEqual( response.body.message, 'Property of a statement cannot be modified' );
		} );
	} );
} );
