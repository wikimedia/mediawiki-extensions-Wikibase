/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createEntitytermsforlanguage = function( options, $node ) {
	options = $.extend( {
		entityId: 'i am an EntityId',
		entityChangersFactory: {
			getAliasesChanger: function() { return 'i am an AliasesChanger'; },
			getDescriptionsChanger: function() {
				return {
					setDescription: function() { return $.Deferred().resolve(); }
				};
			},
			getLabelsChanger: function() {
				return {
					setLabel: function() { return $.Deferred().resolve(); }
				};
			}
		},
		value: {
			language: 'en',
			label: new wb.datamodel.Term( 'en', 'test label' ),
			description: new wb.datamodel.Term( 'en', 'test description' ),
			aliases: new wb.datamodel.MultiTerm( 'en', ['alias1', 'alias2'] )
		}
	}, options || {} );

	$node = $node || $( '<tbody/>' ).appendTo( $( '<table/>' ) );

	var $entitytermsforlanguage = $node
		.addClass( 'test_entitytermsforlanguage' )
		.entitytermsforlanguage( options );

	var entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

	entitytermsforlanguage.$labelview.data( 'labelview' )._save
		= entitytermsforlanguage.$descriptionview.data( 'descriptionview' )._save
		= entitytermsforlanguage.$aliasesview.data( 'aliasesview' )._save
		= function() {
			return $.Deferred().resolve( {
				entity: {
					lastrevid: 'i am a revision id'
				}
			} ).promise();
		};

	return $entitytermsforlanguage;
};

QUnit.module( 'jquery.wikibase.entitytermsforlanguage', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entitytermsforlanguage' ).each( function() {
			var $entitytermsforlanguage = $( this ),
				entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

			if( entitytermsforlanguage ) {
				entitytermsforlanguage.destroy();
			}

			$entitytermsforlanguage.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createEntitytermsforlanguage( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $entitytermsforlanguage = createEntitytermsforlanguage(),
		entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

	assert.ok(
		entitytermsforlanguage !== undefined,
		'Created widget.'
	);

	entitytermsforlanguage.destroy();

	assert.ok(
		$entitytermsforlanguage.data( 'entitytermsforlanguage' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 6, function( assert ) {
	var $entitytermsforlanguage = createEntitytermsforlanguage(),
		entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

	$entitytermsforlanguage
	.on( 'entitytermsforlanguageafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'entitytermsforlanguageafterstopediting', function( event, dropValue ) {
		assert.ok(
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

		if( !expectingEvent ) {
			func();
			return deferred.resolve().promise();
		}

		$entitytermsforlanguage
		.one(
			'entitytermsforlanguageafterstartediting.entitytermsforlanguagetest',
			function( event ) {
				$entitytermsforlanguage.off( '.entitytermsforlanguagetest' );
				deferred.resolve();
			}
		)
		.one(
			'entitytermsforlanguageafterstopediting.entitytermsforlanguagetest',
			function( event, dropValue ) {
				$entitytermsforlanguage.off( '.entitytermsforlanguagetest' );
				deferred.resolve();
			}
		);

		func();

		return deferred.promise();
	}

	var $queue = $( {} );

	/**
	 * @param {jQuery} $queue
	 * @param {Function} func
	 * @param {boolean} [expectingEvent]
	 */
	function addToQueue( $queue, func, expectingEvent ) {
		if( expectingEvent === undefined ) {
			expectingEvent = true;
		}
		$queue.queue( 'tests', function( next ) {
			QUnit.stop();
			testEditModeChange( func, expectingEvent ).always( function() {
				QUnit.start();
				next();
			} );
		} );
	}

	addToQueue( $queue, function() {
		entitytermsforlanguage.startEditing();
	} );

	addToQueue( $queue, function() {
		entitytermsforlanguage.startEditing();
	}, false );

	addToQueue( $queue, function() {
		entitytermsforlanguage.stopEditing( true );
	} );

	addToQueue( $queue, function() {
		entitytermsforlanguage.stopEditing( true );
	}, false );

	addToQueue( $queue, function() {
		entitytermsforlanguage.stopEditing();
	}, false );

	addToQueue( $queue, function() {
		entitytermsforlanguage.startEditing();
	} );

	addToQueue( $queue, function() {
		entitytermsforlanguage.$label.find( 'input' ).val( '' );
		entitytermsforlanguage.stopEditing();
	} );

	addToQueue( $queue, function() {
		entitytermsforlanguage.startEditing();
	} );

	addToQueue( $queue, function() {
		entitytermsforlanguage.$description.find( 'input' ).val( 'changed description' );
		entitytermsforlanguage.stopEditing();
	} );

	$queue.dequeue( 'tests' );
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $entitytermsforlanguage = createEntitytermsforlanguage(),
		entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

	entitytermsforlanguage.startEditing();

	assert.ok(
		entitytermsforlanguage.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	entitytermsforlanguage.$label.find( 'input' ).val( 'changed' );

	assert.ok(
		!entitytermsforlanguage.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	entitytermsforlanguage.$label.find( 'input' ).val( 'test label' );

	assert.ok(
		entitytermsforlanguage.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
	var $entitytermsforlanguage = createEntitytermsforlanguage(),
		entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' );

	$entitytermsforlanguage
	.on( 'entitytermsforlanguagetoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	entitytermsforlanguage.setError();
} );

QUnit.test( 'value()', function( assert ) {
	var $entitytermsforlanguage = createEntitytermsforlanguage(),
		entitytermsforlanguage = $entitytermsforlanguage.data( 'entitytermsforlanguage' ),
		label = new wb.datamodel.Term( 'en', 'changed label' ),
		description = new wb.datamodel.Term( 'en', 'test description' ),
		aliases = new wb.datamodel.MultiTerm( 'en', ['alias1', 'alias2'] );

	assert.throws(
		function() {
			entitytermsforlanguage.value( null );
		},
		'Trying to set no value fails.'
	);

	entitytermsforlanguage.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		entitytermsforlanguage.value().label.equals( label ),
		'Set new label.'
	);

	assert.ok(
		entitytermsforlanguage.value().description.equals( description ),
		'Did not change description.'
	);

	label = new wb.datamodel.Term( 'en', 'test label' );
	description = new wb.datamodel.Term( 'en', '' );

	entitytermsforlanguage.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		entitytermsforlanguage.value().label.equals( label ),
		'Reset label.'
	);

	assert.ok(
		entitytermsforlanguage.value().description.equals( description ),
		'Removed description.'
	);

	aliases = new wb.datamodel.MultiTerm( 'en', ['alias1', 'alias2', 'alias3'] );

	entitytermsforlanguage.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		entitytermsforlanguage.value().aliases.equals( aliases ),
		'Added alias.'
	);

	aliases = new wb.datamodel.MultiTerm( 'en', [] );

	entitytermsforlanguage.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		entitytermsforlanguage.value().aliases.equals( aliases ),
		'Removed aliases.'
	);

	assert.throws(
		function() {
			entitytermsforlanguage.value( {
				language: 'de',
				label: label,
				description: description,
				aliases: aliases
			} );
		},
		'Trying to change language fails.'
	);
} );

}( jQuery, wikibase, QUnit ) );
