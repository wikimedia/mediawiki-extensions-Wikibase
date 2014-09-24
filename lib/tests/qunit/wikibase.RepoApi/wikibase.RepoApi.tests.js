/**
 * QUnit tests for wikibase.RepoApi
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * wb.RepoApi object
	 * @var {Object}
	 */
	var api = new wb.RepoApi();

	/**
	 * Queue used run asynchronous tests synchronously.
	 * @var {jQuery}
	 */
	var testrun = $( {} );

	/**
	 * Queue key naming the queue that all tests are appended to.
	 * @var {String}
	 */
	var qkey = 'asyncTests';

	/**
	 * Since jQuery.queue does not allow passing parameters, this variable will cache the data
	 * structures of entities.
	 * @var {Object}
	 */
	var entityStack = [];

	/**
	 * Triggers running the tests attached to the test queue.
	 */
	var runTest = function() {
		QUnit.stop();
		testrun.queue( qkey, function() {
			QUnit.start(); // finish test run
		} );
		testrun.dequeue( qkey ); // start tests
	};

	/**
	 * Handles a failing API request. (The details get logged in the console by mw.Api.)
	 *
	 * @param {String} code
	 * @param {Object} details
	 */
	var onFail = function( code, details ) {
		QUnit.assert.ok(
			false,
			'API request failed returning code: "' + code + '". See console for details.'
		);
		QUnit.start(); // skip all remaining queued tests
	};

	/**
	 * Creates an entity via the API.
	 *
	 * @param {string} [entityType] Either "item" or "property"
	 * @param {Object} [data] Stringified JSON representing the entity content
	 */
	var createEntity = function( entityType, data ) {
		data = data || {};

		api.createEntity( entityType, data ).done( function( response ) {
			QUnit.assert.equal(
				response.success,
				1,
				'Created ' + entityType + '.'
			);
			entityStack.push( response.entity );
			testrun.dequeue( qkey );
		} ).fail( onFail );
	};

	QUnit.module( 'wikibase.RepoApi', QUnit.newMwEnvironment( {
		teardown: function() {
			entityStack = [];
		}
	} ) );

	// This test does nothing more than creating an empty entity. It would not need to invoke a
	// queue but can be used as basic template for creating other API tests.
	QUnit.test( 'Create an empty entity', function( assert ) {
		testrun.queue( qkey, function() { createEntity( 'item' ); } );
		// testrun.queue( qkey, function() { ...; testrun.dequeue( qkey ); } );
		runTest();
	} );

	QUnit.test( 'Create an empty property', function( assert ) {
		testrun.queue( qkey, function() {
			createEntity( 'property', { datatype: 'string' } );
		} );
		runTest();
	} );

	QUnit.test( 'Search for an entity', function( assert ) {
		var data = {
			labels: { de: {
				language: 'de',
				value: 'de-search-val'
			} }
		};

		testrun.queue( qkey, function() { createEntity( 'item', data ); } );

		testrun.queue( qkey, function() {
			api.searchEntities( data.labels.de.value, data.labels.de.language, 'item', 2, 0 )
				.done( function( response ) {

					assert.ok(
						response.search.length > 0,
						'Search results returned.'
					);
					assert.equal(
						response.search[0].label,
						data.labels.de.value,
						'Verified item label.'
					);

					testrun.dequeue( qkey );
				} )
				.fail( onFail );
		} );

		runTest();

	});

	QUnit.test( 'Edit an entity', function( assert ) {
		testrun.queue( qkey, function() { createEntity( 'item' ); } );

		testrun.queue( qkey, function() {
			var entity = entityStack[0];

			var data = {
				labels: {
					de: {
						language: 'de',
						value: 'de-value'
					},
					en: {
						language: 'en',
						value: 'en-value'
					}
				}
			};

			api.editEntity( entity.id, entity.lastrevid, data ).done( function( response ) {

				assert.equal(
					response.entity.id,
					entity.id,
					'Verified entity id.'
				);

				assert.deepEqual(
					response.entity.labels,
					data.labels,
					'Edited entity.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );

		} );

		runTest();

	} );

	QUnit.test( 'Create a claim (string value)', function( assert ) {

		testrun.queue( qkey, function() { createEntity( 'item' ); } );
		testrun.queue( qkey, function() { createEntity( 'property', { datatype: 'string' } ); } );

		testrun.queue( qkey, function() {
			var entity = entityStack[0],
				property = entityStack[1];

			api.createClaim(
				entity.id,
				entity.lastrevid,
				'value',
				property.id,
				'This claim is true'
			).done( function( response ) {

				assert.equal(
					response.claim.mainsnak.property,
					property.id,
					'Verified claim\'s property id.'
				);

				testrun.dequeue( qkey );

			} ).fail( onFail );

		} );

		runTest();

	} );

	QUnit.test( 'Get claim (string value)', function( assert ) {

		var answer = '42', entity, property;

		testrun.queue( qkey, function() { createEntity( 'item' ); } );
		testrun.queue( qkey, function() { createEntity( 'property', { datatype: 'string' } ); } );

		testrun.queue( qkey, function() {
			entity = entityStack[0];
			property = entityStack[1];

			api.createClaim(
				entity.id,
				entity.lastrevid,
				'value',
				property.id,
				answer
			).done( function( response ) {
				testrun.dequeue( qkey );
			} ).fail( onFail );

		} );

		testrun.queue( qkey, function() {
			api.getClaims( entity.id, property.id ).done( function( response ) {

				assert.ok(
					property.id in response.claims,
					'Claim data for given property found.'
				);

				assert.equal(
					response.claims[property.id][0].mainsnak.datavalue.value,
					answer,
					'Claim value verified.'
				);

				testrun.dequeue( qkey );

			} ).fail( onFail );

		} );

		runTest();

	} );

	/**
	 * Will test the RepoApi's function to set one of the multilingual basic values, e.g. label or
	 * description.
	 *
	 * @param {Object} assert
	 * @param {string} type E.g. 'label' or 'description'
	 * @param {string} apiFnName The name of the wb.RepoApi function to set the value
	 */
	function testSetBasicMultiLingualValue( assert, type, apiFnName ) {
		var entity,
			typesApiPropName = type + 's', // API uses plural form of the type: 'label' -> 'labels'
			language = 'en',
			value = language + '-' + type; // could be anything really

		testrun.queue( qkey, function() { createEntity( 'item' ); } );

		testrun.queue( qkey, function() {
			entity = entityStack[0];

			api[ apiFnName ](
				entity.id, entity.lastrevid, value, language
			).done( function( response ) {

				assert.deepEqual(
					response.entity[ typesApiPropName ].en,
					{ language: language, value: value },
					type + ' "' + value + '" has been set for language "' + language + '"'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			var setTypeApiPromise = api[ apiFnName ](
				entity.id, entity.lastrevid, value, 'non-existent-language'
			);

			assert.ok( // promise is a plain object, so check for 'then' function
				$.isFunction( setTypeApiPromise.then ),
				'wb.RepoApi.' + apiFnName + ' returns a jQuery Promise'
			);


			setTypeApiPromise.done( function( response ) {
				assert.ok(
					false,
					'Impossible to set the ' + type + ' for an unknown language'
				);
				QUnit.start();
			} );

			setTypeApiPromise.fail( function( code, details ) {
				assert.equal(
					code,
					'unknown_language',
					'Failed trying to set the value on an unknown language'
				);
				testrun.dequeue( qkey );
			} );
		} );

		runTest();
	}

	QUnit.test( 'Set label', function( assert ) {
		testSetBasicMultiLingualValue( assert, 'label', 'setLabel' );
	} );

	QUnit.test( 'Set description', function( assert ) {
		testSetBasicMultiLingualValue( assert, 'description', 'setDescription' );
	} );

// TODO Re-activate test after having solved problem with edit conflict detection added in change
// set I344d76812649781c83814afb8e9386ff66fc9086 (commit 3680cedf87a7a45296320b12590432bc50a46c5a)
/*
	QUnit.test( 'Add and remove site links', function( assert ) {
		var data = {
			sitelinks: {
				dewiki: {
					site: 'dewiki',
					title: 'Königsberg in Bayern',
					badges: [], // this relys on config, so for now only an empty array
					url: 'http://de.wikipedia.org/wiki/K%C3%B6nigsberg_in_Bayern'
				}
			}
		};

		var invalidData = {
			sitelinks: {
				dewiki: {
					site: 'doesnotexist',
					title: 'doesnotexist'
				}
			}
		};

		testrun.queue( qkey, function() { createEntity( 'item' ); } );

		testrun.queue( qkey, function() {
			api.setSitelink(
				entity.id,
				entity.lastrevid,
				data.sitelinks.dewiki.site,
				data.sitelinks.dewiki.title,
				data.sitelinks.dewiki.badges
			).done( function( response ) {

				assert.deepEqual(
					response.entity.sitelinks[data.sitelinks.dewiki.site],
					data.sitelinks.dewiki,
					'Set site link.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.getEntities( entity.id, 'sitelinks' ).done( function( response ) {

				delete data.sitelinks.dewiki.url;

				assert.deepEqual(
					response.entities[entity.id].sitelinks,
					data.sitelinks,
					'Verified site link.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.setSitelink(
				entity.id,
				entity.lastrevid,
				invalidData.sitelinks.dewiki.site,
				invalidData.sitelinks.dewiki.title
			).done( function( response ) {

					assert.ok(
						false,
						'It is possible to set an invalid site link.'
					);

					QUnit.start();
				} ).fail( function( code, details ) {
					assert.equal(
						code,
						'unknown_linksite',
						'Failed trying to set an invalid site link.'
					);
					testrun.dequeue( qkey );
				} );
		} );

		testrun.queue( qkey, function() {
			api.getEntities( entity.id, 'sitelinks' ).done( function( response ) {

				assert.deepEqual(
					response.entities[entity.id].sitelinks,
					[],
					'Verified empty site links.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		runTest();

	} );
*/
	QUnit.test( 'Set aliases', function( assert ) {
		testrun.queue( qkey, function() { createEntity( 'item' ); } );

		testrun.queue( qkey, function() {
			api.setAliases(
				entityStack[0].id, entityStack[0].lastrevid, [ 'alias1', 'alias2' ], [], 'en'
			).done( function( response ) {

				assert.deepEqual(
					response.entity.aliases.en,
					[
						{ language: 'en', value: 'alias1' },
						{ language: 'en', value: 'alias2' }
					],
					'Added aliases.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.setAliases(
				entityStack[0].id, entityStack[0].lastrevid, [ 'alias1', 'alias2' ], [], 'doesnotexist'
			).done( function( response ) {

				assert.ok(
					false,
					'It is possible to set aliases for an invalid language.'
				);

				QUnit.start();
			} ).fail( function( code, details ) {
				assert.equal(
					code,
					'unknown_language',
					'Failed trying to set aliases for an invalid language.'
				);
				testrun.dequeue( qkey );
			} );
		} );

// TODO Re-activate after having solved problem with edit conflict detection added in change
// set I344d76812649781c83814afb8e9386ff66fc9086 (commit 3680cedf87a7a45296320b12590432bc50a46c5a)
/*
		testrun.queue( qkey, function() {
			api.setAliases(
				entityStack[0].id, entityStack[0].lastrevid, 'alias3', 'alias1', 'en'
			).done( function( response ) {

				assert.deepEqual(
					response.entity.aliases.en,
					[
						{ language: 'en', value: 'alias2' },
						{ language: 'en', value: 'alias3' }
					],
					'Added and removed aliases.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.setAliases(
				entityStack[0].id, entityStack[0].lastrevid, '', [ 'alias2', 'alias3' ], 'en'
			).done( function( response ) {

				assert.equal(
					response.entity.aliases,
					undefined,
					'Removed aliases.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );
*/
		runTest();

	} );

}( wikibase, jQuery, QUnit ) );
