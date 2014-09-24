/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
var createFingerprintlistview = function( options ) {
	options = $.extend( {
		entityId: 'i am an entity id',
		api: 'i am an api',
		aliasesChanger: 'aliasesChanger',
		value: [
			{
				language: 'de',
				label: 'de-label',
				description: 'de-description',
				aliases: []
			}, {
				language: 'en',
				label: 'en-label',
				description: 'en-description',
				aliases: []
			}
		]
	}, options || {} );

	return $( '<table/>' )
		.appendTo( 'body' )
		.addClass( 'test_fingerprintlistview' )
		.fingerprintlistview( options );
};

QUnit.module( 'jquery.wikibase.fingerprintlistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_fingerprintlistview' ).each( function() {
			var $fingerprintlistview = $( this ),
				fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

			if( fingerprintlistview ) {
				fingerprintlistview.destroy();
			}

			$fingerprintlistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createFingerprintlistview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $fingerprintlistview = createFingerprintlistview(),
		fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

	assert.ok(
		fingerprintlistview !== undefined,
		'Created widget.'
	);

	fingerprintlistview.destroy();

	assert.ok(
		$fingerprintlistview.data( 'fingerprintlistview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $fingerprintlistview = createFingerprintlistview(),
		fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

	fingerprintlistview.startEditing();

	assert.ok(
		fingerprintlistview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	var $item = $fingerprintlistview.data( 'listview' ).addItem( {
		language: 'fa',
		label: 'fa-label',
		description: 'fa-description',
		aliases: []
	} );

	assert.ok(
		!fingerprintlistview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	$fingerprintlistview.data( 'listview' ).removeItem( $item );

	assert.ok(
		fingerprintlistview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

// TODO: Add test which is kind of pointless without having a method to save a whole fingerprint
// which could be overwritten by the test mechanism. Instead, the "save" functions of labelview,
// descriptionview and aliasesview for each single fingerprintview would need to be overwritten
// (see fingerprintview tests).
// QUnit.test( 'startEditing() & stopEditing()', function( assert ) {} );

QUnit.test( 'setError()', function( assert ) {
	var $fingerprintlistview = createFingerprintlistview(),
		fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

	$fingerprintlistview
	.on( 'fingerprintlistviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	fingerprintlistview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	var $fingerprintlistview = createFingerprintlistview(),
		fingerprintlistview = $fingerprintlistview.data( 'fingerprintlistview' );

	// TODO: Enhance test as soon as SiteLinkList is implemented in DataModelJavaScript and used
	// as value object.
	assert.equal(
		fingerprintlistview.value().length,
		2,
		'Retrieved value.'
	);

	assert.throws(
		function() {
			fingerprintlistview.value( [] );
		},
		'Throwing error when trying to set a new value.'
	);
} );

}( jQuery, QUnit ) );
