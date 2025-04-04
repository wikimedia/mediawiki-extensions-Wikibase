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
		options = Object.assign( {
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

	QUnit.skip( 'Create & destroy', ( assert ) => {
		assert.throws(
			() => {
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

	QUnit.test( 'startEditing() & stopEditing()', ( assert ) => {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		$entitytermsforlanguageview
		.on( 'entitytermsforlanguageviewafterstartediting', ( event ) => {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'entitytermsforlanguageviewafterstopediting', ( event, dropValue ) => {
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
				( event ) => {
					$entitytermsforlanguageview.off( '.entitytermsforlanguageviewtest' );
					deferred.resolve();
				}
			)
			.one(
				'entitytermsforlanguageviewafterstopediting.entitytermsforlanguageviewtest',
				( event, dropValue ) => {
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
			$queue.queue( 'tests', ( next ) => {
				var done = assert.async();
				testEditModeChange( func, expectingEvent ).always( () => {
					next();
					done();
				} );
			} );
		}

		addToQueue( () => {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( () => {
			entitytermsforlanguageview.startEditing();
		}, false );

		addToQueue( () => {
			entitytermsforlanguageview.stopEditing( true );
		} );

		addToQueue( () => {
			entitytermsforlanguageview.stopEditing( true );
		}, false );

		addToQueue( () => {
			entitytermsforlanguageview.stopEditing();
		}, false );

		addToQueue( () => {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( () => {
			entitytermsforlanguageview.$label.find( 'input, textarea' ).val( '' );
			entitytermsforlanguageview.stopEditing();
		} );

		addToQueue( () => {
			entitytermsforlanguageview.startEditing();
		} );

		addToQueue( () => {
			entitytermsforlanguageview.$description.find( 'input, textarea' ).val( 'changed description' );
			entitytermsforlanguageview.stopEditing();
		} );

		$queue.dequeue( 'tests' );
	} );

	QUnit.test( 'non-mul description behaviour', ( assert ) => {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		assert.propContains(
			entitytermsforlanguageview.$descriptionview.data( 'descriptionview' ).options,
			{
				readOnly: false,
				accessibilityLabel: null
			},
			'Not read only, no accessibility label passed.'
		);
		assert.notPropContains(
			entitytermsforlanguageview.$descriptionview.data( 'descriptionview' ).options,
			{
				placeholderMessage: 'wikibase-description-edit-placeholder-not-applicable'
			},
			'Mul placeholder message is not used.'
		);
	} );

	QUnit.test( 'mul description behaviour', ( assert ) => {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview( {
				value: {
					language: 'mul',
					label: new datamodel.Term( 'mul', 'test label' ),
					description: new datamodel.Term( 'mul', '' ),
					aliases: new datamodel.MultiTerm( 'mul', [ 'alias1', 'alias2' ] )
				}
			} ),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		assert.propContains(
			entitytermsforlanguageview.$descriptionview.data( 'descriptionview' ).options,
			{
				readOnly: true,
				placeholderMessage: 'wikibase-description-edit-placeholder-not-applicable',
				accessibilityLabel:
					mw.msg( 'wikibase-description-edit-mul-not-applicable-accessibility-label' )
			},
			'Options for mul set: Read only, accessibility label and placeholder message.'
		);
	} );

	QUnit.test( 'setError()', ( assert ) => {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' );

		$entitytermsforlanguageview
		.on( 'entitytermsforlanguageviewtoggleerror', ( event, error ) => {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		entitytermsforlanguageview.setError();
	} );

	QUnit.test( 'value()', ( assert ) => {
		var $entitytermsforlanguageview = createEntitytermsforlanguageview(),
			entitytermsforlanguageview
				= $entitytermsforlanguageview.data( 'entitytermsforlanguageview' ),
			label = new datamodel.Term( 'en', 'changed label' ),
			description = new datamodel.Term( 'en', 'test description' ),
			aliases = new datamodel.MultiTerm( 'en', [ 'alias1', 'alias2' ] );

		assert.throws(
			() => {
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
			() => {
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
