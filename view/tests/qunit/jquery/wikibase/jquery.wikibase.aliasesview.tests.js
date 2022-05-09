/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var createAliasesview = function ( options ) {
		options = $.extend( {
			value: new datamodel.MultiTerm( 'en', [ 'a', 'b', 'c' ] )
		}, options || {} );

		var $aliasesview = $( '<div>' )
			.addClass( 'test_aliasesview' )
			.appendTo( document.body )
			.aliasesview( options );

		$aliasesview.data( 'aliasesview' )._save = function () {
			return $.Deferred().resolve().promise();
		};

		return $aliasesview;
	};

	QUnit.module( 'jquery.wikibase.aliasesview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_aliasesview' ).each( function () {
				var $aliasesview = $( this ),
					aliasesview = $aliasesview.data( 'aliasesview' );

				if ( aliasesview ) {
					aliasesview.destroy();
				}

				$aliasesview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.throws(
			function () {
				createAliasesview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $aliasesview = createAliasesview(),
			aliasesview = $aliasesview.data( 'aliasesview' );

		assert.true(
			aliasesview instanceof $.wikibase.aliasesview,
			'Created widget'
		);

		aliasesview.destroy();

		assert.strictEqual(
			$aliasesview.data( 'aliasesview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Instantiating tagadata widget on startEditing()', function ( assert ) {
		var $aliasesview = createAliasesview(),
			aliasesview = $aliasesview.data( 'aliasesview' );

		return aliasesview.startEditing().done( function () {
			assert.notStrictEqual(
				aliasesview.$list.data( 'tagadata' ),
				undefined,
				'Instantiated tagadata widget.'
			);
		} );
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $aliasesview = createAliasesview(),
			aliasesview = $aliasesview.data( 'aliasesview' ),
			done = assert.async();

		$aliasesview
		.on( 'aliasesviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'aliasesviewafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		/**
		 * @param {Function} func
		 * @param {boolean} expectingEvent
		 * @return {Object} jQuery.Promise
		 */
		function testEditModeChange( func, expectingEvent ) {
			var deferred = $.Deferred();

			if ( !expectingEvent ) {
				func();
				return deferred.resolve().promise();
			}

			$aliasesview
			.one( 'aliasesviewafterstartediting.aliasesviewtest', function ( event ) {
				$aliasesview.off( '.aliasesviewtest' );
				deferred.resolve();
			} )
			.one( 'aliasesviewafterstopediting.aliasesviewtest', function ( event, dropValue ) {
				$aliasesview.off( '.aliasesviewtest' );
				deferred.resolve();
			} );

			func();

			return deferred.promise();
		}

		var $queue = $( {} );

		/**
		 * @param {Function} func
		 * @param {boolean} [expectingEvent]
		 */
		function addToQueue( func, expectingEvent ) {
			if ( expectingEvent === undefined ) {
				expectingEvent = true;
			}
			$queue.queue( 'tests', function ( next ) {
				var changeDone = assert.async();
				testEditModeChange( func, expectingEvent ).always( function () {
					next();
					changeDone();
				} );
			} );
		}

		addToQueue( function () {
			aliasesview.startEditing();
		} );

		addToQueue( function () {
			aliasesview.startEditing();
		}, false );

		addToQueue( function () {
			aliasesview.stopEditing( true );
		} );

		addToQueue( function () {
			aliasesview.stopEditing( true );
		}, false );

		addToQueue( function () {
			aliasesview.stopEditing();
		}, false );

		addToQueue( function () {
			aliasesview.startEditing();
		} );

		addToQueue( function () {
			aliasesview.$list.data( 'tagadata' ).getTags().first().find( 'input' ).val( 'b' );
			aliasesview.stopEditing();
		} );

		addToQueue( function () {
			aliasesview.startEditing();
		} );

		addToQueue( function () {
			aliasesview.$list.data( 'tagadata' ).getTags().first()
				.removeClass( 'tagadata-choice-equal' ).find( 'input' ).val( 'd' );
			aliasesview.stopEditing();
		} );

		$queue.dequeue( 'tests' );

		done();
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $aliasesview = createAliasesview(),
			aliasesview = $aliasesview.data( 'aliasesview' );

		$aliasesview
		.on( 'aliasesviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		aliasesview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $aliasesview = createAliasesview(),
			aliasesview = $aliasesview.data( 'aliasesview' ),
			newValue = null;

		assert.throws(
			function () {
				aliasesview.value( newValue );
			},
			'Trying to set no value fails.'
		);

		newValue = new datamodel.MultiTerm( 'de', [ 'x', 'y' ] );
		aliasesview.value( newValue );

		assert.strictEqual(
			aliasesview.value().equals( newValue ),
			true,
			'Set new value.'
		);

		newValue = new datamodel.MultiTerm( 'en', [] );
		aliasesview.value( newValue );

		assert.strictEqual(
			aliasesview.value().equals( newValue ),
			true,
			'Set another value.'
		);
	} );

}() );
