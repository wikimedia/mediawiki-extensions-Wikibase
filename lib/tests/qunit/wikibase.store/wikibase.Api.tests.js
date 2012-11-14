/**
 * QUnit tests for wkibase.Api
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * wb.Api object
	 * @var {Object}
	 */
	var api = new wb.Api();

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

		api.editEntity( data ).done( function( response ) {
			QUnit.assert.equal(
				response.success,
				1,
				'Created item.'
			);
			entity = response.entity;
			testrun.dequeue( qkey );
		} ).fail( onFail );
	};


	QUnit.module( 'wikibase.Api', QUnit.newWbEnvironment() );

	// This test does nothing more than creating an empty entity. It would not need to invoke a
	// queue but can be used as basic template for creating other API tests.
	QUnit.test( 'Create an empy entity', function( assert ) {
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

	QUnit.test( 'Set label(s)', function( assert ) {
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

		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
			api.setLabel(
				entity.id, entity.lastrevid, data.labels.de.value, data.labels.de.language
			).done( function( response ) {

				assert.deepEqual(
					response.entity.labels.de,
					data.labels.de,
					'Set label.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.setLabel(
				entity.id, entity.lastrevid, data.labels.en.value, data.labels.en.language
			).done( function( response ) {

				assert.deepEqual(
					response.entity.labels.en,
					data.labels.en,
					'Set second label.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.getEntities( entity.id, 'labels' ).done( function( response ) {

				assert.deepEqual(
					response.entities[entity.id].labels,
					data.labels,
					'Verified labels.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		runTest();

	} );

	QUnit.test( 'Set description(s)', function( assert ) {
		var data = {
			descriptions: {
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

		testrun.queue( qkey, function() { createItem(); } );

		testrun.queue( qkey, function() {
			api.setDescription(
				entity.id, entity.lastrevid, data.descriptions.de.value, data.descriptions.de.language
			).done( function( response ) {

				assert.deepEqual(
					response.entity.descriptions.de,
					data.descriptions.de,
					'Set description.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.setDescription(
				entity.id, entity.lastrevid, data.descriptions.en.value, data.descriptions.en.language
			).done( function( response ) {

				assert.deepEqual(
					response.entity.descriptions.en,
					data.descriptions.en,
					'Set second description.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		testrun.queue( qkey, function() {
			api.getEntities( entity.id, 'descriptions' ).done( function( response ) {

				assert.deepEqual(
					response.entities[entity.id].descriptions,
					data.descriptions,
					'Verified descriptions.'
				);

				testrun.dequeue( qkey );
			} ).fail( onFail );
		} );

		runTest();

	} );

}( wikibase, jQuery, QUnit ) );
