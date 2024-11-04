'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newCreatePropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );
const { assertValidError } = require( '../helpers/responseValidator' );

describe( newCreatePropertyRequestBuilder().getRouteDescription(), () => {

	const maxLabelLength = 250;
	const valueTooLong = 'x'.repeat( maxLabelLength + 1 );
	let predicatePropertyId;

	before( async () => {
		const predicateProperty = await newCreatePropertyRequestBuilder( { data_type: 'string' } )
			.assertValidRequest()
			.makeRequest();
		predicatePropertyId = predicateProperty.body.id;

	} );

	describe( '201 success response ', () => {
		it( 'can create a minimal property', async () => {
			const property = { data_type: 'string' };
			const response = await newCreatePropertyRequestBuilder( property )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 201 );
			assert.header( response, 'Location', `${response.request.url}/${response.body.id}` );
			assert.deepEqual( response.body.data_type, property.data_type );

			const editMetadata = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.header( response, 'etag', makeEtag( editMetadata.revid ) );
			assert.header( response, 'last-modified', editMetadata.timestamp );
		} );

		it( 'can create a property with all fields', async () => {
			const labels = { en: `instance of-${ utils.uniq() }` };
			const descriptions = { en: 'that class of which this subject is a particular example and member' };
			const aliases = { en: [ 'is a', 'type' ] };
			const data_type = 'string';

			const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).body.id;
			const statementValue = '99 Bottles of Milk';
			const statements = {
				[ statementPropertyId ]: [ {
					property: { id: statementPropertyId },
					value: { type: 'value', content: statementValue }
				} ]
			};

			const response = await newCreatePropertyRequestBuilder( {
				data_type,
				labels,
				descriptions,
				aliases,
				statements
			} ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 201 );
			assert.deepEqual( response.body.labels, labels );
			assert.deepEqual( response.body.descriptions, descriptions );
			assert.deepEqual( response.body.aliases, aliases );
			assert.strictEqual( response.body.statements[ statementPropertyId ][ 0 ].value.content, statementValue );
			assert.deepEqual( response.body.data_type, 'string' );
		} );

		it( 'can create a property with edit metadata provided', async () => {
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i created a property';

			const response = await newCreatePropertyRequestBuilder( { data_type: 'string' } )
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
				`/* wbeditentity-create-property:0| */ ${editSummary}`
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );
	} );

	describe( '400', () => {
		it( 'responds with missing-field error without a data type', async () => {
			const response = await newCreatePropertyRequestBuilder( {} ).assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: '/property', field: 'data_type' }
			);
		} );

		it( 'responds with missing-field error when statement is missing a mandatory field ', async () => {
			const statements = { [ predicatePropertyId ]: [ { property: { id: predicatePropertyId } } ] };
			const response = await newCreatePropertyRequestBuilder( { data_type: 'string', statements } ).makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: `/property/statements/${predicatePropertyId}/0`, field: 'value' }
			);
		} );

		Object.entries( {
			label: {
				property: { data_type: 'string', labels: { en: valueTooLong } },
				invalidFieldPath: '/property/labels/en'
			},
			description: {
				property: { data_type: 'string', descriptions: { en: valueTooLong } },
				invalidFieldPath: '/property/descriptions/en'
			},
			alias: {
				property: { data_type: 'string', aliases: { en: [ valueTooLong ] } },
				invalidFieldPath: '/property/aliases/en/0'
			}
		} ).forEach( ( [ field, { property, invalidFieldPath } ] ) => {
			it( `value too long: ${field}`, async () => {
				const response = await newCreatePropertyRequestBuilder( property ).makeRequest();

				assertValidError( response, 400, 'value-too-long', { path: invalidFieldPath, limit: maxLabelLength } );
				assert.strictEqual( response.body.message, 'The input value is too long' );
			} );
		} );

		Object.entries( {
			'invalid label language code': {
				property: { data_type: 'string', labels: { invalidLanguageCode: 'label' } },
				invalidFieldPath: '/property/labels'
			},
			'invalid description language code': {
				property: { data_type: 'string', descriptions: { invalidLanguageCode: 'description' } },
				invalidFieldPath: '/property/descriptions'
			},
			'invalid alias language code': {
				property: { data_type: 'string', aliases: { invalidLanguageCode: [ 'label' ] } },
				invalidFieldPath: '/property/aliases'
			}
		} ).forEach( ( [ reason, { property, invalidFieldPath } ] ) => {
			it( `invalid-key: ${reason}`, async () => {
				const response = await newCreatePropertyRequestBuilder( property ).makeRequest();

				assertValidError( response, 400, 'invalid-key', { path: invalidFieldPath, key: 'invalidLanguageCode' } );
			} );
		} );

		Object.entries( {
			'invalid data_type field type': {
				property: { data_type: 123 },
				invalidFieldPath: '/property/data_type'
			},
			'invalid data_type field': {
				property: { data_type: 'invalid-type' },
				invalidFieldPath: '/property/data_type'
			},
			'invalid labels field type': {
				property: { data_type: 'string', labels: 'not an object' },
				invalidFieldPath: '/property/labels'
			},
			'invalid descriptions field type': {
				property: { data_type: 'string', descriptions: 'not an object' },
				invalidFieldPath: '/property/descriptions'
			},
			'invalid aliases field type': {
				property: { data_type: 'string', aliases: 'not an object' },
				invalidFieldPath: '/property/aliases'
			},
			'invalid statements field type': {
				property: { data_type: 'string', statements: 'not an object' },
				invalidFieldPath: '/property/statements'
			},
			'invalid labels': {
				property: { data_type: 'string', labels: [ 'not an associative array' ] },
				invalidFieldPath: '/property/labels'
			},
			'empty label': {
				property: { data_type: 'string', labels: { en: '' } },
				invalidFieldPath: '/property/labels/en'
			},
			'invalid label type': {
				property: { data_type: 'string', labels: { en: [ 'invalid', 'label', 'type' ] } },
				invalidFieldPath: '/property/labels/en'
			},
			'invalid label': {
				property: { data_type: 'string', labels: { en: 'tab characters \t not allowed' } },
				invalidFieldPath: '/property/labels/en'
			},
			'invalid descriptions': {
				property: { data_type: 'string', descriptions: [ 'not a valid descriptions array' ] },
				invalidFieldPath: '/property/descriptions'
			},
			'empty description': {
				property: { data_type: 'string', descriptions: { en: '' } },
				invalidFieldPath: '/property/descriptions/en'
			},
			'invalid description type': {
				property: { data_type: 'string', descriptions: { en: 22 } },
				invalidFieldPath: '/property/descriptions/en'
			},
			'invalid description': {
				property: { data_type: 'string', descriptions: { en: 'tab characters \t not allowed' } },
				invalidFieldPath: '/property/descriptions/en'
			},
			'invalid aliases': {
				property: { data_type: 'string', aliases: [ 'not a valid aliases array' ] },
				invalidFieldPath: '/property/aliases'
			},
			'invalid aliases in language type': {
				property: { data_type: 'string', aliases: { en: 'not a list of aliases in a language' } },
				invalidFieldPath: '/property/aliases/en'
			},
			'empty aliases in language': {
				property: { data_type: 'string', aliases: { en: [] } },
				invalidFieldPath: '/property/aliases/en'
			},
			'invalid alias type': {
				property: { data_type: 'string', aliases: { en: [ 123, 'second alias' ] } },
				invalidFieldPath: '/property/aliases/en/0'
			},
			'invalid alias': {
				property: { data_type: 'string', aliases: { en: [ 'valid alias', 'tabs \t not \t allowed' ] } },
				invalidFieldPath: '/property/aliases/en/1'
			},
			'empty alias': {
				property: { data_type: 'string', aliases: { en: [ 'alias', '' ] } },
				invalidFieldPath: '/property/aliases/en/1'
			}
		} ).forEach( ( [ reason, { property, invalidFieldPath } ] ) => {
			it( `invalid value: ${reason}`, async () => {
				const response = await newCreatePropertyRequestBuilder( property ).makeRequest();

				assertValidError( response, 400, 'invalid-value', { path: invalidFieldPath } );
			} );
		} );

		Object.entries( {
			'invalid statement group type': {
				property: () => ( {
					data_type: 'string',
					statements: { [ predicatePropertyId ]: { 1: {
						property: { id: predicatePropertyId },
						value: { type: 'value', content: 'some-value' }
					} } }
				} ),
				invalidFieldPath: () => `/property/statements/${predicatePropertyId}`
			},
			'invalid statement type': {
				property: () => ( {
					data_type: 'string',
					statements: { [ predicatePropertyId ]: [ 'invalid-statement-type' ] }
				} ),
				invalidFieldPath: () => `/property/statements/${predicatePropertyId}/0`
			},
			'invalid statement field': {
				property: () => ( {
					data_type: 'string',
					statements: { [ predicatePropertyId ]: [ {
						property: { id: predicatePropertyId },
						value: { type: 123, content: 'some value' }
					} ] }
				} ),
				invalidFieldPath: () => '/property/statements/' + predicatePropertyId + '/0/value/type'
			}
		} ).forEach( ( [ reason, { property, invalidFieldPath } ] ) => {
			it( `invalid value: ${reason}`, async () => {
				const response = await newCreatePropertyRequestBuilder( property() ).makeRequest();

				assertValidError( response, 400, 'invalid-value', { path: invalidFieldPath() } );
			} );
		} );

		it( 'responds with statement-group-property-id-mismatch when statement property id mismatch', async () => {
			const propertyIdKey = 'P123';
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const property = { data_type: 'string', statements: { [ propertyIdKey ]: [ validStatement ] } };

			const response = await newCreatePropertyRequestBuilder( property ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'statement-group-property-id-mismatch',
				{
					path: `/property/statements/${propertyIdKey}/0/property/id`,
					statement_group_property_id: propertyIdKey,
					statement_property_id: predicatePropertyId
				}
			);
			assert.equal( response.body.message, "Statement's Property ID does not match the Statement group key" );
		} );

		it( 'responds with referenced-resource-not-found error when statement property id does not exist', async () => {
			const nonExistentProperty = 'P9999999';
			const statement = entityHelper.newStatementWithRandomStringValue( nonExistentProperty );

			const response = await newCreatePropertyRequestBuilder( {
				data_type: 'string',
				statements: { [ nonExistentProperty ]: [ statement ] }
			} ).makeRequest();

			assertValidError(
				response,
				400,
				'referenced-resource-not-found',
				{ path: `/property/statements/${nonExistentProperty}/0/property/id` }
			);
			assert.strictEqual( response.body.message, 'The referenced resource does not exist' );
		} );

	} );

	describe( '422', () => {
		it( 'responds with data-policy-violation error when label and description with the same value', async () => {
			const languageCode = 'en';
			const sameValueForLabelAndDescription = 'a random value';

			const propertyToCreate = {
				data_type: 'string',
				labels: { [ languageCode ]: sameValueForLabelAndDescription },
				descriptions: { [ languageCode ]: sameValueForLabelAndDescription }
			};

			const response = await newCreatePropertyRequestBuilder( propertyToCreate ).makeRequest();

			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: languageCode } }
			);
		} );

		it( 'responds with data-policy-violation error when property with the same label already exists', async () => {
			const languageCode = 'en';
			const label = `test-label-${utils.uniq()}`;

			const property = { data_type: 'string', labels: { [ languageCode ]: label } };

			const existingEntityResponse = await newCreatePropertyRequestBuilder( property ).assertValidRequest().makeRequest();
			const existingPropertyId = existingEntityResponse.body.id;

			const response = await newCreatePropertyRequestBuilder( property ).assertValidRequest().makeRequest();

			const context = { language: languageCode, conflicting_property_id: existingPropertyId };
			assertValidError(
				response,
				422,
				'data-policy-violation',
				{ violation: 'property-label-duplicate', violation_context: context }
			);
		} );
	} );
} );
