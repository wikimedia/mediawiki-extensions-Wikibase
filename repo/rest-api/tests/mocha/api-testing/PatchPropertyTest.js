'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const {
	newPatchPropertyRequestBuilder,
	newAddPropertyStatementRequestBuilder,
	newGetPropertyLabelRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { formatWholeEntityEditSummary } = require( '../helpers/formatEditSummaries' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

describe( newPatchPropertyRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const languageWithExistingLabel = 'en';
	const languageWithExistingDescription = 'en';

	function assertValid200Response( response ) {
		expect( response ).to.have.status( 200 );
		assert.strictEqual( response.body.id, testPropertyId );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	before( async function () {
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: languageWithExistingLabel, value: `some-label-${utils.uniq()}` } ],
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

			assertValid200Response( response );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				// eslint-disable-next-line security/detect-non-literal-regexp
				new RegExp( `^${testPropertyId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.strictEqual(
				editMetadata.comment,
				formatWholeEntityEditSummary( 'update-languages-and-other-short', 'de, en, fr', editSummary )
			);
		} );

		it( 'allows content-type application/json-patch+json', async () => {
			const expectedValue = `new english label ${utils.uniq()}`;
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{
					op: 'add',
					path: '/labels/en',
					value: expectedValue
				}
			] )
				.withHeader( 'content-type', 'application/json-patch+json' )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.labels.en, expectedValue );
		} );

		it( 'allows content-type application/json', async () => {
			const expectedValue = `new english label ${utils.uniq()}`;
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{
					op: 'add',
					path: '/labels/en',
					value: expectedValue
				}
			] )
				.withHeader( 'content-type', 'application/json' )
				.assertValidRequest().makeRequest();

			assertValid200Response( response );
			assert.strictEqual( response.body.labels.en, expectedValue );
		} );

		it( 'can patch other fields even if there is a statement using a deleted property', async () => {
			const propertyToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			await newAddPropertyStatementRequestBuilder(
				testPropertyId,
				{ property: { id: propertyToDelete }, value: { type: 'novalue' } }
			).makeRequest();

			await entityHelper.deleteProperty( propertyToDelete );
			await runAllJobs(); // wait for secondary data to catch up after deletion

			const label = `some-label-${utils.uniq()}`;
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: '/labels/de', value: label } ]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.labels.de, label );
		} );
	} );

	describe( '400 Bad Request', () => {
		it( 'property ID is invalid', async () => {
			const response = await newPatchPropertyRequestBuilder( 'X123', [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyRequestBuilder( testPropertyId, patch ) );

		it( 'comment too long', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
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

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const enLabel = ( await newGetPropertyLabelRequestBuilder( testPropertyId, 'en' ).makeRequest() ).body;

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: enLabel } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );
	} );

	describe( '422 error response', () => {
		it( 'invalid operation change property id', async () => {
			const patch = [
				{ op: 'replace', path: '/id', value: 'P666' }
			];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-property-invalid-operation-change-property-id' );
			assert.strictEqual( response.body.message, 'Cannot change the ID of the existing property' );
		} );

		it( 'invalid operation change property datatype', async () => {
			const patch = [
				{ op: 'replace', path: '/data_type', value: 'wikibase-item' }
			];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-property-invalid-operation-change-property-datatype' );
			assert.strictEqual( response.body.message, 'Cannot change the datatype of the existing property' );
		} );

		it( 'missing mandatory field', async () => {
			const patch = [ { op: 'remove', path: '/data_type' } ];
			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: '', field: 'data_type' };
			assertValidError( response, 422, 'patch-result-missing-field', context );
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
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingLabelPatchOp( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/labels/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'invalid label language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ {
					op: 'add',
					path: `/labels/${invalidLanguage}`,
					value: 'potato'
				} ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/labels', key: invalidLanguage } );
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

			const context = {
				violation: 'property-label-duplicate',
				violation_context: {
					language: languageWithExistingLabel,
					conflicting_property_id: existingPropertyId
				}
			};
			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
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
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ makeReplaceExistingDescriptionPatchOperation( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/descriptions/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'invalid description language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[ { op: 'add', path: `/descriptions/${invalidLanguage}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/descriptions', key: invalidLanguage } );
		} );

		it( 'label-description-same-value', async () => {
			const language = languageWithExistingLabel;
			const text = `label-and-description-text-${utils.uniq()}`;

			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[
					{ op: 'replace', path: '/labels/en', value: text },
					{ op: 'replace', path: '/descriptions/en', value: text }
				]
			).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
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
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ 'x'.repeat( maxLength + 1 ) ] }
			] ).assertValidRequest().makeRequest();

			const context = { path: `/aliases/${language}/0`, limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
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
				'patch-result-invalid-value',
				{ path: `/aliases/${language}`, value: invalidAliasesValue }
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
				'patch-result-invalid-value',
				{ path: `/aliases/${language}/0`, value: invalidAlias }
			);
		} );

		it( 'invalid aliases language code', async () => {
			const invalidLanguage = 'not-a-valid-language';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [
				{ op: 'add', path: `/aliases/${invalidLanguage}`, value: [ 'alias' ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/aliases', key: invalidLanguage } );
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

			const context = { path: `/statements/${predicatePropertyId}`, value: validStatement };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );

		} );

		it( 'invalid statement type', async () => {
			const invalidStatement = [ {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			} ];
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0`, value: invalidStatement };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid statements type', async () => {
			const invalidStatements = [ 'invalid statements type' ];
			const patch = [ { op: 'add', path: '/statements', value: invalidStatements } ];

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: '/statements', value: invalidStatements };
			assertValidError( response, 422, 'patched-property-invalid-field', context );
			assert.strictEqual( response.body.message, "Invalid input for '/statements' in the patched property" );
		} );

		it( 'invalid statement field', async () => {
			const invalidRankValue = 'invalid rank';
			const invalidStatement = { rank: invalidRankValue };
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0/rank`, value: invalidRankValue };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'missing statement field', async () => {
			const invalidStatement = { value: { type: 'somevalue', content: 'some-content' } };
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchPropertyRequestBuilder( testPropertyId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0`, field: 'property' };
			assertValidError( response, 422, 'patch-result-missing-field', context );
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
				statement_group_property_id: propertyIdKey,
				statement_property_id: predicatePropertyId
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

			const context = { statement_id: invalidStatement.id };
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

			const context = { statement_id: duplicateStatement.id };
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

			const context = { statement_id: existingStatementsId, statement_property_id: predicatePropertyId };
			assertValidError( response, 422, 'patched-statement-property-not-modifiable', context );
			assert.strictEqual( response.body.message, 'Property of a statement cannot be modified' );
		} );
	} );
} );
