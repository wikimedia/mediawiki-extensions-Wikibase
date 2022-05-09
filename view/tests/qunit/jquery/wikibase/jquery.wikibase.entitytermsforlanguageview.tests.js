/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createEntitytermsforlanguageview = function ( options, $node ) {
		options = $.extend( {
			value: {
				language: 'en',
				label: new datamodel.Term( 'en', 'test label' ),
				description: new datamodel.Term( 'en', 'test description' ),
				aliases: new datamodel.MultiTerm( 'en', [ 'alias1', 'alias2' ] )
			}
		}, options || {} );

		$node = $node || $( '<tbody>' ).appendTo( $( '<table>' ) );

		var $entitytermsforlanguageview = $node
			.addClass( 'test_entitytermsforlanguageview' )
			.entitytermsforlanguageview( options );

		return $entitytermsforlanguageview;
	};

	QUnit.module( 'jquery.wikibase.entitytermsforlanguageview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_entitytermsforlanguageview' ).each( function () {
				var $entitytermsforlanguageview = $( this ),
					entitytermsforlanguageview
						= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

				if ( entitytermsforlanguageview ) {
					entitytermsforlanguageview.destroy();
				}

				$entitytermsforlanguageview.remove();
			} );
		}
	} ) );

	QUnit.skip( 'Create & destroy', function ( assert ) {
		assert.throws(
			function () {
				createEntitytermsforlanguageview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		assert.true(
			entitytermsforlanguageview !== undefined,
			'Created widget.'
		);

		entitytermsforlanguageview.destroy();

		assert.true(
			$entitytermsforlanguageview.data( 'entitytermsforlanguageview' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		$entitytermsforlanguageview
		.on( 'entitytermsforlanguageviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'entitytermsforlanguageviewafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		/**
		 * @param {Function} func
		 * @param {boolean} expectingEvent
		 * @return {jQuery.Promise}
		 */
		function testEditModeChange( func, expectingEvent ) {
			var deferred = $.Deferred();

			if ( !expectingEvent ) {
				func();
				return deferred.resolve().promise();
			}

			$entitytermsforlanguageview
			.one(
				'entitytermsforlanguageviewafterstartediting.entitytermsforlanguageviewtest',
				function ( event ) {
					$entitytermsforlanguageview.off( '.entitytermsforlanguageviewtest' );
					deferred.resolve();
				}
			)
			.one(
				'entitytermsforlanguageviewafterstopediting.entitytermsforlanguageviewtest',
				function ( event, dropValue ) {
					$entitytermsforlanguageview.off( '.entitytermsforlanguageviewtest' );
					deferred.resolve();
				}
			);

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
				var done = assert.async();
				testEditModeChange( func, expectingEvent ).always( function () {
					next();
					done();
				} );
			} );
		}

		addToQueue( function () {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( function () {
			entitytermsforlanguageview.startEditing();
		}, false );

		addToQueue( function () {
			entitytermsforlanguageview.stopEditing( true );
		} );

		addToQueue( function () {
			entitytermsforlanguageview.stopEditing( true );
		}, false );

		addToQueue( function () {
			entitytermsforlanguageview.stopEditing();
		}, false );

		addToQueue( function () {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( function () {
			entitytermsforlanguageview.$label.find( 'input, textarea' ).val( '' );
			entitytermsforlanguageview.stopEditing();
		} );

		addToQueue( function () {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( function () {
			entitytermsforlanguageview.$description.find( 'input, textarea' ).val( 'changed description' );
			entitytermsforlanguageview.stopEditing();
		} );

		$queue.dequeue( 'tests' );
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		$entitytermsforlanguageview
		.on( 'entitytermsforlanguageviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		entitytermsforlanguageview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' ),
			label = new datamodel.Term( 'en', 'changed label' ),
			description = new datamodel.Term( 'en', 'test description' ),
			aliases = new datamodel.MultiTerm( 'en', [ 'alias1', 'alias2' ] );

		assert.throws(
			function () {
				entitytermsforlanguageview.value( null );
			},
			'Trying to set no value fails.'
		);

		entitytermsforlanguageview.value( {
			language: 'en',
			label: label,
			description: description,
			aliases: aliases
		} );

		assert.true(
			entitytermsforlanguageview.value().label.equals( label ),
			'Set new label.'
		);

		assert.true(
			entitytermsforlanguageview.value().description.equals( description ),
			'Did not change description.'
		);

		label = new datamodel.Term( 'en', 'test label' );
		description = new datamodel.Term( 'en', '' );

		entitytermsforlanguageview.value( {
			language: 'en',
			label: label,
			description: description,
			aliases: aliases
		} );

		assert.true(
			entitytermsforlanguageview.value().label.equals( label ),
			'Reset label.'
		);

		assert.true(
			entitytermsforlanguageview.value().description.equals( description ),
			'Removed description.'
		);

		aliases = new datamodel.MultiTerm( 'en', [ 'alias1', 'alias2', 'alias3' ] );

		entitytermsforlanguageview.value( {
			language: 'en',
			label: label,
			description: description,
			aliases: aliases
		} );

		assert.true(
			entitytermsforlanguageview.value().aliases.equals( aliases ),
			'Added alias.'
		);

		aliases = new datamodel.MultiTerm( 'en', [] );

		entitytermsforlanguageview.value( {
			language: 'en',
			label: label,
			description: description,
			aliases: aliases
		} );

		assert.true(
			entitytermsforlanguageview.value().aliases.equals( aliases ),
			'Removed aliases.'
		);

		assert.throws(
			function () {
				entitytermsforlanguageview.value( {
					language: 'de',
					label: label,
					description: description,
					aliases: aliases
				} );
			},
			'Trying to change language fails.'
		);
	} );

}() );
