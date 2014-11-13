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
var createFingerprintview = function( options, $node ) {
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

	var $fingerprintview = $node
		.addClass( 'test_fingerprintview' )
		.fingerprintview( options );

	var fingerprintview = $fingerprintview.data( 'fingerprintview' );

	fingerprintview.$labelview.data( 'labelview' )._save
		= fingerprintview.$descriptionview.data( 'descriptionview' )._save
		= fingerprintview.$aliasesview.data( 'aliasesview' )._save
		= function() {
			return $.Deferred().resolve( {
				entity: {
					lastrevid: 'i am a revision id'
				}
			} ).promise();
		};

	return $fingerprintview;
};

QUnit.module( 'jquery.wikibase.fingerprintview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_fingerprintview' ).each( function() {
			var $fingerprintview = $( this ),
				fingerprintview = $fingerprintview.data( 'fingerprintview' );

			if( fingerprintview ) {
				fingerprintview.destroy();
			}

			$fingerprintview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createFingerprintview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $fingerprintview = createFingerprintview(),
		fingerprintview = $fingerprintview.data( 'fingerprintview' );

	assert.ok(
		fingerprintview !== undefined,
		'Created widget.'
	);

	fingerprintview.destroy();

	assert.ok(
		$fingerprintview.data( 'fingerprintview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 6, function( assert ) {
	var $fingerprintview = createFingerprintview(),
		fingerprintview = $fingerprintview.data( 'fingerprintview' );

	$fingerprintview
	.on( 'fingerprintviewafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'fingerprintviewafterstopediting', function( event, dropValue ) {
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

		$fingerprintview
		.one( 'fingerprintviewafterstartediting.fingerprintviewtest', function( event ) {
			$fingerprintview.off( '.fingerprintviewtest' );
			deferred.resolve();
		} )
		.one( 'fingerprintviewafterstopediting.fingerprintviewtest', function( event, dropValue ) {
			$fingerprintview.off( '.fingerprintviewtest' );
			deferred.resolve();
		} );

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
		fingerprintview.startEditing();
	} );

	addToQueue( $queue, function() {
		fingerprintview.startEditing();
	}, false );

	addToQueue( $queue, function() {
		fingerprintview.stopEditing( true );
	} );

	addToQueue( $queue, function() {
		fingerprintview.stopEditing( true );
	}, false );

	addToQueue( $queue, function() {
		fingerprintview.stopEditing();
	}, false );

	addToQueue( $queue, function() {
		fingerprintview.startEditing();
	} );

	addToQueue( $queue, function() {
		fingerprintview.$label.find( 'input' ).val( '' );
		fingerprintview.stopEditing();
	} );

	addToQueue( $queue, function() {
		fingerprintview.startEditing();
	} );

	addToQueue( $queue, function() {
		fingerprintview.$description.find( 'input' ).val( 'changed description' );
		fingerprintview.stopEditing();
	} );

	$queue.dequeue( 'tests' );
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $fingerprintview = createFingerprintview(),
		fingerprintview = $fingerprintview.data( 'fingerprintview' );

	fingerprintview.startEditing();

	assert.ok(
		fingerprintview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	fingerprintview.$label.find( 'input' ).val( 'changed' );

	assert.ok(
		!fingerprintview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	fingerprintview.$label.find( 'input' ).val( 'test label' );

	assert.ok(
		fingerprintview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
	var $fingerprintview = createFingerprintview(),
		fingerprintview = $fingerprintview.data( 'fingerprintview' );

	$fingerprintview
	.on( 'fingerprintviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	fingerprintview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	var $fingerprintview = createFingerprintview(),
		fingerprintview = $fingerprintview.data( 'fingerprintview' ),
		label = new wb.datamodel.Term( 'en', 'changed label' ),
		description = new wb.datamodel.Term( 'en', 'test description' ),
		aliases = new wb.datamodel.MultiTerm( 'en', ['alias1', 'alias2'] );

	assert.throws(
		function() {
			fingerprintview.value( null );
		},
		'Trying to set no value fails.'
	);

	fingerprintview.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		fingerprintview.value().label.equals( label ),
		'Set new label.'
	);

	assert.ok(
		fingerprintview.value().description.equals( description ),
		'Did not change description.'
	);

	label = new wb.datamodel.Term( 'en', 'test label' );
	description = new wb.datamodel.Term( 'en', '' );

	fingerprintview.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		fingerprintview.value().label.equals( label ),
		'Reset label.'
	);

	assert.ok(
		fingerprintview.value().description.equals( description ),
		'Removed description.'
	);

	aliases = new wb.datamodel.MultiTerm( 'en', ['alias1', 'alias2', 'alias3'] );

	fingerprintview.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		fingerprintview.value().aliases.equals( aliases ),
		'Added alias.'
	);

	aliases = new wb.datamodel.MultiTerm( 'en', [] );

	fingerprintview.value( {
		language: 'en',
		label: label,
		description: description,
		aliases: aliases
	} );

	assert.ok(
		fingerprintview.value().aliases.equals( aliases ),
		'Removed aliases.'
	);

	assert.throws(
		function() {
			fingerprintview.value( {
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
