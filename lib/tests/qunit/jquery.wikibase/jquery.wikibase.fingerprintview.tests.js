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
		entityId: 'i am an entity id',
		api: 'i am an api',
		value: {
			language: 'en',
			label: 'test label',
			description: 'test description'
		}
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $fingerprintview = $node
		.addClass( 'test_fingerprintview' )
		.fingerprintview( options );

	$fingerprintview.data( 'fingerprintview' ).$label.data( 'labelview' )._save
		= $fingerprintview.data( 'fingerprintview' ).$description.data( 'descriptionview' )._save
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

	fingerprintview.startEditing();

	fingerprintview.startEditing(); // should not trigger event
	fingerprintview.stopEditing( true );
	fingerprintview.stopEditing( true ); // should not trigger event
	fingerprintview.stopEditing(); // should not trigger event
	fingerprintview.startEditing();

	fingerprintview.$label.find( 'input' ).val( '' );

	fingerprintview.stopEditing();
	fingerprintview.startEditing();

	fingerprintview.$description.find( 'input' ).val( 'changed description' );

	fingerprintview.stopEditing();
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
		fingerprintview = $fingerprintview.data( 'fingerprintview' );

	assert.throws(
		function() {
			fingerprintview.value( null );
		},
		'Trying to set no value fails.'
	);

	fingerprintview.value( {
		language: 'en',
		label: 'changed label',
		description: 'test description'
	} );

	assert.ok(
		fingerprintview.value().label,
		'changed label',
		'Set new label.'
	);

	assert.equal(
		fingerprintview.value().language,
		'en',
		'Did not change language.'
	);

	assert.equal(
		fingerprintview.value().description,
		'test description',
		'Did not change description.'
	);

	fingerprintview.value( {
		language: 'en',
		label: 'test label',
		description: null
	} );

	assert.equal(
		fingerprintview.value().label,
		'test label',
		'Reset label.'
	);

	assert.strictEqual(
		fingerprintview.value().description,
		null,
		'Removed description.'
	);

	assert.throws(
		function() {
			fingerprintview.value( {
				language: 'de',
				label: 'test label',
				description: null
			} );
		},
		'Trying to change language fails.'
	);
} );

}( jQuery, wikibase, QUnit ) );
