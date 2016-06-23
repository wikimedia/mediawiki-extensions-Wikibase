/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createDescriptionview = function( options, $node ) {
	options = $.extend( {
		value: new wb.datamodel.Term( 'en', 'test description' )
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $descriptionview = $node
		.addClass( 'test_descriptionview' )
		.descriptionview( options );

	return $descriptionview;
};

QUnit.module( 'jquery.wikibase.descriptionview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_descriptionview' ).each( function() {
			var $descriptionview = $( this ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if ( descriptionview ) {
				descriptionview.destroy();
			}

			$descriptionview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	assert.throws(
		function() {
			createDescriptionview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	assert.ok(
		descriptionview instanceof $.wikibase.descriptionview,
		'Created widget.'
	);

	descriptionview.destroy();

	assert.ok(
		$descriptionview.data( 'descriptionview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 5, function( assert ) {
	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	$descriptionview
	.on( 'descriptionviewafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'descriptionviewafterstopediting', function( event, dropValue ) {
		assert.ok(
			true,
			'Stopped edit mode.'
		);
	} );

	descriptionview.startEditing();

	assert.ok(
		descriptionview.$text.find( 'textarea' ).length === 1,
		'Generated textarea element.'
	);

	descriptionview.startEditing(); // should not trigger event
	descriptionview.stopEditing( true );
	descriptionview.stopEditing( true ); // should not trigger event
	descriptionview.stopEditing(); // should not trigger event
	descriptionview.startEditing();

	descriptionview.$text.find( 'textarea' ).val( '' );

	descriptionview.stopEditing();
} );

QUnit.test( 'setError()', function( assert ) {
	assert.expect( 1 );
	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	$descriptionview
	.on( 'descriptionviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	descriptionview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 3 );
	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' ),
		newValue = null;

	assert.throws(
		function() {
			descriptionview.value( newValue );
		},
		'Trying to set no value fails.'
	);

	newValue = new wb.datamodel.Term( 'de', 'changed description' );

	descriptionview.value( newValue );

	assert.ok(
		descriptionview.value().equals( newValue ),
		'Set new value.'
	);

	newValue = new wb.datamodel.Term( 'en', '' );

	descriptionview.value( newValue );

	assert.ok(
		descriptionview.value().equals( newValue ),
		'Set another value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
