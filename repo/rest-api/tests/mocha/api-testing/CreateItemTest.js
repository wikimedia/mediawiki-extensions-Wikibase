'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/botUser' );

describe( newCreateItemRequestBuilder().getRouteDescription(), () => {

	let localWikiId;
	let testWikiPage;
	let predicatePropertyId;

	before( async () => {
		localWikiId = await entityHelper.getLocalSiteId();
		testWikiPage = utils.title( 'Sitelink test page' );
		await entityHelper.createWikiPage( testWikiPage );
		predicatePropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
	} );

	describe( '201 success response ', () => {
		it( 'can create an empty item', async () => {
			const response = await newCreateItemRequestBuilder( {} ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 201 );
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
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
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
		it( 'comment too long', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: 'a test item' } } )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: 'a test item' } } )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: 'a test item' } } )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: 'a test item' } } )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'invalid comment', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: 'a test item' } } )
				.withJsonBodyParam( 'comment', 123 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );

		it( 'invalid top-level field', async () => {
			const fieldWithInvalidValue = 'labels';
			const invalidValue = 'not an object';
			const invalidItem = { [ fieldWithInvalidValue ]: invalidValue };

			const response = await newCreateItemRequestBuilder( invalidItem ).assertInvalidRequest().makeRequest();

			const context = { path: `/item/${fieldWithInvalidValue}` };
			assertValidError( response, 400, 'invalid-value', context );
			assert.strictEqual( response.body.message, `Invalid value at '/item/${fieldWithInvalidValue}'` );
		} );

		it( 'invalid labels field', async () => {
			const invalidLabels = [ 'not a valid labels object' ];
			const response = await newCreateItemRequestBuilder( { labels: invalidLabels } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/labels' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/labels'" );
		} );

		it( 'invalid descriptions field', async () => {
			const invalidDescriptions = [ 'not a valid descriptions object' ];
			const response = await newCreateItemRequestBuilder( { descriptions: invalidDescriptions } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-value',
				{ path: '/item/descriptions' }
			);
			assert.strictEqual( response.body.message, "Invalid value at '/item/descriptions'" );
		} );

		it( 'invalid aliases field', async () => {
			const labels = { en: 'item label' };
			const invalidAliases = [ 'not a valid aliases object' ];
			const response = await newCreateItemRequestBuilder( { labels, aliases: invalidAliases } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/aliases' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/aliases'" );
		} );

		it( 'invalid statements field', async () => {
			const labels = { en: 'item label' };
			const invalidStatements = [ 'not a valid statements object' ];
			const response = await newCreateItemRequestBuilder( { labels, statements: invalidStatements } )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/statements' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/statements'" );
		} );

		it( 'unexpected field', async () => {
			const unexpectedField = 'foo';
			const item = {
				labels: { en: 'English label' },
				[ unexpectedField ]: 'bar'
			};

			const response = await newCreateItemRequestBuilder( item ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'unexpected-field', { field: unexpectedField } );
			assert.strictEqual( response.body.message, 'The request body contains an unexpected field' );
		} );

		it( 'invalid label language code', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { xyz: 'label' } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-key', { path: '/item/labels', key: 'xyz' } );
			assert.strictEqual( response.body.message, "Invalid key 'xyz' in '/item/labels'" );
		} );

		it( 'invalid description language code', async () => {
			const response = await newCreateItemRequestBuilder( { descriptions: { xyz: 'description' } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-key', { path: '/item/descriptions', key: 'xyz' } );
			assert.strictEqual( response.body.message, "Invalid key 'xyz' in '/item/descriptions'" );
		} );

		it( 'invalid label', async () => {
			const invalidLabel = 'tab characters \t not allowed';
			const response = await newCreateItemRequestBuilder( { labels: { en: invalidLabel } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/labels/en' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/labels/en'" );
		} );

		it( 'invalid description', async () => {
			const invalidDescription = 'tab characters \t not allowed';
			const response = await newCreateItemRequestBuilder( { descriptions: { en: invalidDescription } } )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/descriptions/en' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/descriptions/en'" );
		} );

		it( 'empty label', async () => {
			const response = await newCreateItemRequestBuilder( { labels: { en: '' } } )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/labels/en' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/labels/en'" );
		} );

		it( 'empty description', async () => {
			const response = await newCreateItemRequestBuilder( { descriptions: { en: '' } } )
				.assertValidRequest().makeRequest();

			const context = { path: '/item/descriptions/en' };
			assertValidError( response, 400, 'invalid-value', context );
			assert.strictEqual( response.body.message, "Invalid value at '/item/descriptions/en'" );
		} );

		it( 'label too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLabelLength = 250;
			const labelTooLong = 'x'.repeat( maxLabelLength + 1 );
			const response = await newCreateItemRequestBuilder( { labels: { en: labelTooLong } } )
				.assertValidRequest().makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/item/labels/en', limit: maxLabelLength } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'description too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxDescriptionLength = 250;
			const descriptionTooLong = 'x'.repeat( maxDescriptionLength + 1 );
			const response = await newCreateItemRequestBuilder( { descriptions: { en: descriptionTooLong } } )
				.assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'value-too-long',
				{ path: '/item/descriptions/en', limit: maxDescriptionLength }
			);
			assert.strictEqual( response.body.message, 'The input value is too long' );
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
				422,
				'data-policy-violation',
				{ violation: 'label-description-same-value', violation_context: { language: languageCode } }
			);
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
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

			const context = {
				violation: 'item-label-description-duplicate',
				violation_context: {
					language: languageCode,
					conflicting_item_id: existingItemId
				}
			};

			assertValidError( response, 422, 'data-policy-violation', context );
			assert.strictEqual( response.body.message, 'Edit violates data policy' );
		} );

		it( 'invalid aliases language code', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { xyz: [ 'alias' ] }
			} )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-key', { path: '/item/aliases', key: 'xyz' } );
			assert.strictEqual( response.body.message, "Invalid key 'xyz' in '/item/aliases'" );
		} );

		it( 'alias is empty', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: [ 'en-alias-1', '' ] }
			} )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/aliases/en/1' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/aliases/en/1'" );
		} );

		it( 'alias list is empty', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: [] }
			} )
				.assertValidRequest()
				.makeRequest();

			const path = '/item/aliases/en';
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'alias list is invalid', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: 'not a list' }
			} )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-alias-list', { language: 'en' } );
			assert.strictEqual( response.body.message, 'Not a valid alias list' );
		} );

		it( 'alias too long', async () => {
			// this assumes the default value of 250 from Wikibase.default.php is in place and
			// may fail if $wgWBRepoSettings['string-limits']['multilang']['length'] is overwritten
			const maxLength = 250;
			const aliasTooLong = 'x'.repeat( maxLength + 1 );
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: [ aliasTooLong ] }
			} )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/item/aliases/en/0', limit: maxLength } );
			assert.strictEqual( response.body.message, 'The input value is too long' );

		} );

		it( 'alias contains invalid characters', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: [ 'tab characters \t not allowed' ] }
			} )
				.assertValidRequest()
				.makeRequest();

			const path = '/item/aliases/en/0';
			assertValidError( response, 400, 'invalid-value', { path } );
			assert.include( response.body.message, path );
		} );

		it( 'duplicate input aliases', async () => {
			const duplicateAlias = 'foo';
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				aliases: { en: [ duplicateAlias, duplicateAlias ] }
			} )
				.assertValidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'duplicate-alias',
				{ alias: duplicateAlias, language: 'en' }
			);
			assert.include( response.body.message, duplicateAlias );
		} );

		it( 'invalid statement group type', async () => {
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const invalidStatementGroupType = { 1: validStatement };

			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				statements: { [ predicatePropertyId ]: invalidStatementGroupType }
			} ).assertInvalidRequest().makeRequest();

			const path = '/item/statements/' + predicatePropertyId;
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'invalid statement type', async () => {
			const invalidStatement = [ {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			} ];

			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				statements: { [ predicatePropertyId ]: [ invalidStatement ] }
			} ).assertInvalidRequest().makeRequest();

			const path = `/item/statements/${predicatePropertyId}/0`;

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'invalid statement field', async () => {
			const invalidStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'invalid', content: 'some value' }
			};

			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				statements: { [ predicatePropertyId ]: [ invalidStatement ] }
			} ).assertInvalidRequest().makeRequest();

			const path = `/item/statements/${predicatePropertyId}/0/value/type`;
			assertValidError( response, 400, 'invalid-value', { path } );
			assert.include( response.body.message, path );
		} );

		it( 'missing top-level field', async () => {
			const response = await newCreateItemRequestBuilder( {} )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'item' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'missing statement field', async () => {
			const statementWthMissingField = { property: { id: predicatePropertyId }, value: { type: 'value' } };
			const itemToCreate = {
				labels: { en: 'en-label' },
				statements: { [ predicatePropertyId ]: [ statementWthMissingField ] }
			};

			const response = await newCreateItemRequestBuilder( itemToCreate )
				.assertValidRequest()
				.makeRequest();

			const context = { path: `/item/statements/${predicatePropertyId}/0/value`, field: 'content' };
			assertValidError( response, 400, 'missing-field', context );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'statement property id mismatch', async () => {
			const propertyIdKey = 'P123';
			const validStatement = {
				property: { id: predicatePropertyId },
				value: { type: 'value', content: 'some-value' }
			};
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				statements: { [ propertyIdKey ]: [ validStatement ] }
			} ).assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'statement-group-property-id-mismatch',
				{
					path: `${propertyIdKey}/0/property/id`,
					statement_group_property_id: propertyIdKey,
					statement_property_id: predicatePropertyId
				}
			);
			assert.equal( response.body.message, "Statement's Property ID does not match the statement group key" );
		} );

		it( 'invalid site ID', async () => {
			const invalidSiteId = 'not-a-valid-site-id';
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ invalidSiteId ]: { title: testWikiPage } }
			} )
				// .assertInvalidRequest() - valid per OAS because it only checks whether it is a string
				.makeRequest();

			assertValidError( response, 400, 'invalid-key', { path: '/item/sitelinks', key: `${invalidSiteId}` } );
			assert.strictEqual( response.body.message, `Invalid key '${invalidSiteId}' in '/item/sitelinks'` );
		} );

		it( 'sitelinks not an object', async () => {
			const invalidSitelinks = [ { title: testWikiPage } ];
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: invalidSitelinks
			} ).makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/item/sitelinks' } );
			assert.strictEqual( response.body.message, "Invalid value at '/item/sitelinks'" );
		} );

		it( 'sitelink is not an object', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: 'not an object' }
			} ).makeRequest();

			assertValidError( response, 400, 'invalid-sitelink-type', { site_id: localWikiId } );
			assert.strictEqual( response.body.message, 'Not a valid sitelink type' );
		} );

		it( 'title is empty', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: '' } }
			} ).makeRequest();
			const path = `/item/sitelinks/${localWikiId}/title`;

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'sitelink title field not provided', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: {} }
			} ).makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: `/item/sitelinks/${localWikiId}`, field: 'title' }
			);
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid title', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: 'invalid title%00' } }
			} ).makeRequest();
			const path = `/item/sitelinks/${localWikiId}/title`;

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'title is not a string', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: [ 'array', 'not', 'allowed' ] } }
			} ).makeRequest();
			const path = `/item/sitelinks/${localWikiId}/title`;

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( 'badges is not an array', async () => {
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: testWikiPage, badges: 'Q123' } }
			} ).makeRequest();

			const path = `/item/sitelinks/${localWikiId}/badges`;
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'badge is not an item ID', async () => {
			const invalidBadge = 'P33';
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: testWikiPage, badges: [ invalidBadge ] } }
			} ).makeRequest();

			const path = `/item/sitelinks/${localWikiId}/badges/0`;
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'provided item is not an allowed badge', async () => {
			const badge = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: testWikiPage, badges: [ badge ] } }
			} ).makeRequest();

			const path = `/item/sitelinks/${localWikiId}/badges/0`;
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'badge item does not exist', async () => {
			const badge = 'Q99999999';
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title: testWikiPage, badges: [ badge ] } }
			} )
				.withHeader( 'X-Wikibase-CI-Badges', badge )
				.makeRequest();

			const path = `/item/sitelinks/${localWikiId}/badges/0`;
			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.strictEqual( response.body.message, `Invalid value at '${path}'` );
		} );

		it( 'sitelink title does not exist', async () => {
			const title = utils.title( 'does-not-exist-' );
			const response = await newCreateItemRequestBuilder( {
				labels: { en: 'en-label' },
				sitelinks: { [ localWikiId ]: { title } }
			} ).makeRequest();

			assertValidError( response, 400, 'title-does-not-exist', { site_id: localWikiId } );
			assert.strictEqual(
				response.body.message,
				`Page with title ${title} does not exist on the given site`
			);
		} );
	} );

	it( '422 sitelink conflict', async () => {
		const linkedArticle = utils.title( 'Potato' );
		await entityHelper.createWikiPage( linkedArticle );

		const createItemWithSitelink = newCreateItemRequestBuilder( {
			labels: { en: 'en-label' },
			sitelinks: { [ localWikiId ]: { title: linkedArticle } }
		} );
		const existingItemWithSitelink = await createItemWithSitelink.makeRequest();
		expect( existingItemWithSitelink ).to.have.status( 201 );

		const response = await createItemWithSitelink.makeRequest();

		const context = {
			violation: 'sitelink-conflict',
			violation_context: { site_id: localWikiId, conflicting_item_id: existingItemWithSitelink.body.id }
		};

		assertValidError( response, 422, 'data-policy-violation', context );
		assert.strictEqual( response.body.message, 'Edit violates data policy' );
	} );
} );
