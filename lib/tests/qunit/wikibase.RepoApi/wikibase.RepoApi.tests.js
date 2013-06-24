/**
 * QUnit tests for wikibase.RepoApi
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
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
	 * Since jQuery.queue does not allow passing parameters, this variable will cache an entity's.
	 * data structure.
	 * @var {Object}
	 */
	var entity = null;

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
	 * @param {Object} [data] Stringified JSON representing the item content
	 */
	var createItem = function( data ) {
		data = data || {};

		api.createEntity( 'item', data ).done( function( response ) {
			QUnit.assert.equal(
				response.success,
				1,
				'Created item.'
			);
			entity = response.entity;
			testrun.dequeue( qkey );
		} ).fail( onFail );
	};


	QUnit.module( 'wikibase.RepoApi', QUnit.newWbEnvironment() );

	// This test does nothing more than creating an empty entity. It would not need to invoke a
	// queue but can be used as basic template for creating other API tests.
	QUnit.test( 'Create an empty entity', function( assert ) {
		testrun.queue( qkey, function() { createItem(); } );
		// testrun.queue( qkey, function() { ...; testrun.dequeue( qkey ); } );
		runTest();
	} );

	QUnit.test( 'Edit an entity', function( assert ) {
		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
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

	/**
	 * Will test the RepoApi's function to set one of the multilingual basic values, e.g. label or
	 * description.
	 *
	 * @param {Object} assert
	 * @param {string} type E.g. 'label' or 'description'
	 * @param {string} apiFnName The name of the wb.RepoApi function to set the value
	 */
	function testSetBasicMultiLingualValue( assert, type, apiFnName ) {
		var typesApiPropName = type + 's', // API uses plural form of the type: 'label' -> 'labels'
			language = 'en',
			value = language + '-' + type; // could be anything really

		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
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

		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
			api.setSitelink(
				entity.id, entity.lastrevid, data.sitelinks.dewiki.site, data.sitelinks.dewiki.title
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
			api.removeSitelink(
				entity.id, entity.lastrevid, data.sitelinks.dewiki.site
			).done( function( response ) {

				assert.equal(
					response.success,
					1,
					'Removed site link.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
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
		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
			api.setAliases(
				entity.id, entity.lastrevid, [ 'alias1', 'alias2' ], [], 'en'
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
				entity.id, entity.lastrevid, [ 'alias1', 'alias2' ], [], 'doesnotexist'
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
				entity.id, entity.lastrevid, 'alias3', 'alias1', 'en'
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
				entity.id, entity.lastrevid, '', [ 'alias2', 'alias3' ], 'en'
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
