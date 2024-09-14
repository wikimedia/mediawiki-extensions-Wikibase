'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const { newPatchItemRequestBuilder, newAddItemStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { formatWholeEntityEditSummary } = require( '../helpers/formatEditSummaries' );
const { createEntity, createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );
const { runAllJobs } = require( 'api-testing/lib/wiki' );

describe( newPatchItemRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const testEnglishLabel = `some-label-${utils.uniq()}`;
	const languageWithExistingLabel = 'en';
	const languageWithExistingDescription = 'en';
	const existingEnAlias = 'pseudonym';
	let allowedBadges;
	let siteId;
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	before( async function () {
		testItemId = ( await entityHelper.createEntity( 'item', {
			labels: [ { language: languageWithExistingLabel, value: testEnglishLabel } ],
			descriptions: [ { language: languageWithExistingDescription, value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'en', value: existingEnAlias }, { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;
		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
		allowedBadges = await getAllowedBadges();

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
					{ op: 'add', path: '/aliases/en/-', value: 'new en alias' },
					{ op: 'add', path: '/aliases/en/-', value: existingEnAlias },
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
			assert.deepStrictEqual( response.body.aliases, { en: [ existingEnAlias, 'new en alias' ] } );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				// eslint-disable-next-line security/detect-non-literal-regexp
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

		it( 'can patch other fields even if there is a statement using a deleted property', async () => {
			const propertyToDelete = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			await newAddItemStatementRequestBuilder(
				testItemId,
				{ property: { id: propertyToDelete }, value: { type: 'novalue' } }
			).makeRequest();

			await entityHelper.deleteProperty( propertyToDelete );
			await runAllJobs(); // wait for secondary data to catch up after deletion

			const label = `some-label-${utils.uniq()}`;
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/labels/de', value: label } ]
			).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.labels.de, label );
		} );
	} );

	describe( '400 error response ', () => {
		it( 'item ID is invalid', async () => {
			const itemId = 'X123';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		testValidatesPatch( ( patch ) => newPatchItemRequestBuilder( testItemId, patch ) );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q99999';
			const response = await newPatchItemRequestBuilder( itemId, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409 error response', () => {

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			const context = { path: '/patch/0/path' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			const context = { path: '/patch/0/from' };
			assertValidError( response, 409, 'patch-target-not-found', context );
			assert.strictEqual( response.body.message, 'Target not found on resource' );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const response = await newPatchItemRequestBuilder( testItemId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { path: '/patch/0', actual_value: testEnglishLabel } );
			assert.strictEqual( response.body.message, 'Test operation in the provided patch failed' );
		} );

		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newPatchItemRequestBuilder( redirectSource, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
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

			assertValidError( response, 422, 'patch-result-modified-read-only-value', { path: '/id' } );
			assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
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

			const context = { path: '/labels', value: invalidLabels };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid label', async () => {
			const language = languageWithExistingLabel;
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/labels/${language}`, value: invalidLabel };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid label type', async () => {
			const invalidLabel = { object: 'not allowed' };
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( invalidLabel ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/labels/${languageWithExistingLabel}`, value: invalidLabel };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'empty label', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( '' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/labels/${languageWithExistingLabel}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingLabelPatchOp( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/labels/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'invalid label language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ {
					op: 'add',
					path: `/labels/${invalidLanguage}`,
					value: 'potato'
				} ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/labels', key: invalidLanguage } );
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

			const context = { path: '/descriptions', value: invalidDescriptions };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/descriptions/${languageWithExistingDescription}`, value: invalidDescription };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid description type', async () => {
			const invalidDescription = { object: 'not allowed' };
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( invalidDescription ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/descriptions/${languageWithExistingDescription}`, value: invalidDescription };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'empty description', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( '' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/descriptions/${languageWithExistingDescription}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'empty description after trimming whitespace in the input', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( ' \t ' ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/descriptions/${languageWithExistingDescription}`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingDescriptionPatchOperation( 'x'.repeat( maxLength + 1 ) ) ]
			).assertValidRequest().makeRequest();

			const context = { path: '/descriptions/en', limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'invalid description language code', async () => {
			const invalidLanguage = 'invalid-language-code';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: `/descriptions/${invalidLanguage}`, value: 'potato' } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/descriptions', key: invalidLanguage } );
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

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: languageWithExistingLabel } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
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
				violation: 'item-label-description-duplicate',
				violation_context: {
					language: languageWithExistingLabel,
					conflicting_item_id: existingItemId
				}
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'empty alias', async () => {
			const language = 'de';
			const response = await newPatchItemRequestBuilder( testItemId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ '' ] }
			] ).assertValidRequest().makeRequest();

			const context = { path: `/aliases/${language}/0`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'alias too long', async () => {
			const language = 'de';
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const response = await newPatchItemRequestBuilder( testItemId, [
				{ op: 'add', path: `/aliases/${language}`, value: [ 'x'.repeat( maxLength + 1 ) ] }
			] ).assertValidRequest().makeRequest();

			const context = { path: `/aliases/${language}/0`, limit: maxLength };
			assertValidError( response, 422, 'patch-result-value-too-long', context );
			assert.strictEqual( response.body.message, 'Patched value is too long' );
		} );

		it( 'aliases in language not a list', async () => {
			const language = 'en';
			const invalidAliasesValue = { 'aliases in language': 'not a list' };
			const response = await newPatchItemRequestBuilder( testItemId, [
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
			const invalidAliasesValue = [ 'not', 'an', 'object' ];
			const response = await newPatchItemRequestBuilder( testItemId, [
				{ op: 'add', path: '/aliases', value: invalidAliasesValue }
			] ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				422,
				'patch-result-invalid-value',
				{ path: '/aliases', value: invalidAliasesValue }
			);
		} );

		it( 'alias contains invalid characters', async () => {
			const language = 'en';
			const invalidAlias = 'tab\t tab\t tab';
			const response = await newPatchItemRequestBuilder( testItemId, [
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
			const response = await newPatchItemRequestBuilder( testItemId, [
				{ op: 'add', path: `/aliases/${invalidLanguage}`, value: [ 'alias' ] }
			] ).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/aliases', key: invalidLanguage } );
		} );

		const makeStatementPatchOperation = ( propertyId, invalidStatement ) => [ {
			op: 'add',
			path: '/statements',
			value: { [ propertyId ]: [ invalidStatement ] }
		} ];

		it( 'invalid statements type', async () => {
			const invalidStatements = [ 'invalid statements type' ];
			const patch = [ { op: 'add', path: '/statements', value: invalidStatements } ];

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: '/statements', value: invalidStatements };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid statement group type', async () => {
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const invalidStatementGroupType = { [ predicatePropertyId ]: validStatement };
			const patch = [ { op: 'add', path: '/statements', value: invalidStatementGroupType } ];

			const response = await newPatchItemRequestBuilder( testItemId, patch )
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

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0`, value: invalidStatement };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'missing statement field', async () => {
			const patch = makeStatementPatchOperation( predicatePropertyId, { value: { type: 'novalue' } } );

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0`, field: 'property' };
			assertValidError( response, 422, 'patch-result-missing-field', context );
		} );

		it( 'invalid statement field', async () => {
			const invalidRankValue = 'invalid rank';
			const invalidStatement = { rank: invalidRankValue };
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0/rank`, value: invalidRankValue };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'statement property id mismatch', async () => {
			const propertyIdKey = 'P123';
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const patch = makeStatementPatchOperation( propertyIdKey, validStatement );

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = {
				path: `${propertyIdKey}/0/property/id`,
				statement_group_property_id: propertyIdKey,
				statement_property_id: predicatePropertyId
			};
			assertValidError( response, 422, 'patched-statement-group-property-id-mismatch', context );
			assert.strictEqual( response.body.message, "Statement's Property ID does not match the statement group key" );
		} );

		it( 'statement IDs cannot be created or modified', async () => {
			const invalidStatement = {
				id: 'P123$4YY2B0D8-BEC1-4D30-B88E-347E08AFD987',
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const patch = makeStatementPatchOperation( predicatePropertyId, invalidStatement );

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0/id` };
			assertValidError( response, 422, 'patch-result-modified-read-only-value', context );
			assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
		} );

		it( 'duplicate statement id', async () => {
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

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${predicatePropertyId}/0/id` };
			assertValidError( response, 422, 'patch-result-modified-read-only-value', context );
			assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
		} );

		it( 'property IDs modified', async () => {
			const newPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const existingStatementId = ( await newAddItemStatementRequestBuilder( testItemId, {
				property: { id: predicatePropertyId },
				value: { type: 'novalue' }
			} ).makeRequest() ).body.id;
			const invalidStatement = {
				id: existingStatementId,
				property: { id: newPropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const patch = makeStatementPatchOperation( newPropertyId, invalidStatement );

			const response = await newPatchItemRequestBuilder( testItemId, patch )
				.assertValidRequest().makeRequest();

			const context = { path: `/statements/${newPropertyId}/0/property/id` };
			assertValidError( response, 422, 'patch-result-modified-read-only-value', context );
			assert.strictEqual( response.body.message, 'Read only value in patch result cannot be modified' );
		} );

		const makeReplaceExistingSitelinkPatchOperation = ( newSitelink ) => ( {
			op: 'replace',
			path: '/sitelinks',
			value: { [ siteId ]: newSitelink }
		} );

		it( 'sitelink is not an object', async () => {
			const invalidSitelinkType = 'not-valid-sitelink-type';

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/sitelinks', value: { [ siteId ]: invalidSitelinkType } } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patched-invalid-sitelink-type', { site_id: siteId } );
			assert.strictEqual( response.body.message, 'Not a valid sitelink type in patched sitelinks' );
		} );

		it( 'sitelinks not an object', async () => {
			const invalidSitelinks = [ { title: linkedArticle } ];

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/sitelinks', value: invalidSitelinks } ]
			).assertValidRequest().makeRequest();

			const context = { path: '/sitelinks', value: invalidSitelinks };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid site id', async () => {
			const invalidSiteId = 'not-valid-site-id';
			const sitelink = { title: linkedArticle, badges: [ allowedBadges[ 0 ] ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/sitelinks', value: { [ invalidSiteId ]: sitelink } } ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'patch-result-invalid-key', { path: '/sitelinks', key: invalidSiteId } );
		} );

		it( 'missing title', async () => {
			const sitelink = { badges: [ allowedBadges[ 0 ] ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}`, field: 'title' };
			assertValidError( response, 422, 'patch-result-missing-field', context );
		} );

		it( 'empty title', async () => {
			const sitelink = { title: '', badges: [ allowedBadges[ 0 ] ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/title`, value: '' };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'invalid title', async () => {
			const title = 'invalid??%00';
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( { title } ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/title`, value: title };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'title does not exist', async () => {
			const title = 'this_title_does_not_exist';
			const sitelink = { title, badges: [ allowedBadges[ 0 ] ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/title`, value: title };
			assertValidError( response, 422, 'patch-result-referenced-resource-not-found', context );
			assert.strictEqual( response.body.message, 'The referenced resource does not exist' );
		} );

		it( 'invalid badge', async () => {
			const badge = 'not-an-item-id';
			const sitelink = { title: linkedArticle, badges: [ badge ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/badges/0`, value: badge };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'item not a badge', async () => {
			const notBadgeItemId = 'Q113';
			const sitelink = { title: linkedArticle, badges: [ notBadgeItemId ] };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/badges/0`, value: notBadgeItemId };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
		} );

		it( 'badges are not a list', async () => {
			const badgesWithInvalidFormat = 'Q113, Q232, Q444';
			const sitelink = { title: linkedArticle, badges: badgesWithInvalidFormat };

			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ makeReplaceExistingSitelinkPatchOperation( sitelink ) ]
			).assertValidRequest().makeRequest();

			const context = { path: `/sitelinks/${siteId}/badges`, value: badgesWithInvalidFormat };
			assertValidError( response, 422, 'patch-result-invalid-value', context );
			assert.strictEqual( response.body.message, 'Invalid value in patch result' );
		} );

		it( 'sitelink conflict', async () => {
			await newPatchItemRequestBuilder(
				testItemId,
				[ { op: 'add', path: '/sitelinks', value: { [ siteId ]: { title: linkedArticle } } } ]
			).assertValidRequest().makeRequest();

			const newItem = await createEntity( 'item', {} );
			const response = await newPatchItemRequestBuilder(
				newItem.entity.id,
				[ { op: 'add', path: '/sitelinks', value: { [ siteId ]: { title: linkedArticle } } } ]
			).assertValidRequest().makeRequest();

			const context = {
				violation: 'sitelink-conflict',
				violation_context: { site_id: siteId, conflicting_item_id: testItemId }
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'url is modified', async () => {
			const response = await newPatchItemRequestBuilder(
				testItemId,
				[ {
					op: 'add',
					path: '/sitelinks',
					value: { [ siteId ]: {
						title: linkedArticle,
						url: 'https://en.wikipedia.org/wiki/Example.com'
					} }
				} ]
			).assertValidRequest().makeRequest();

			assertValidError( response, 422, 'url-not-modifiable', { site_id: siteId } );
			assert.equal( response.body.message, 'URL of sitelink cannot be modified' );
		} );
	} );

} );
