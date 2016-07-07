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
var createLabelview = function( options, $node ) {
	options = $.extend( {
		value: new wb.datamodel.Term( 'en', 'test label' )
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $labelview = $node
		.addClass( 'test_labelview' )
		.labelview( options );

	return $labelview;
};

QUnit.module( 'jquery.wikibase.labelview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_labelview' ).each( function() {
			var $labelview = $( this ),
				labelview = $labelview.data( 'labelview' );

			if ( labelview ) {
				labelview.destroy();
			}

			$labelview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	assert.throws(
		function() {
			createLabelview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	assert.ok(
		labelview instanceof $.wikibase.labelview,
		'Created widget.'
	);

	labelview.destroy();

	assert.ok(
		$labelview.data( 'labelview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 5, function( assert ) {
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	$labelview
	.on( 'labelviewafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'labelviewafterstopediting', function( event, dropValue ) {
		assert.ok(
			true,
			'Stopped edit mode.'
		);
	} );

	labelview.startEditing();

	assert.ok(
		labelview.$text.find( 'textarea' ).length === 1,
		'Generated input element.'
	);

	labelview.startEditing(); // should not trigger event
	labelview.stopEditing( true );
	labelview.stopEditing( true ); // should not trigger event
	labelview.stopEditing(); // should not trigger event
	labelview.startEditing();

	labelview.$text.find( 'textarea' ).val( '' );

	labelview.stopEditing();
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	assert.expect( 3 );
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	labelview.startEditing();

	assert.ok(
		labelview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	labelview.$text.find( 'textarea' ).val( 'changed' );

	assert.ok(
		!labelview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	labelview.$text.find( 'textarea' ).val( 'test label' );

	assert.ok(
		labelview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
	assert.expect( 1 );
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	$labelview
	.on( 'labelviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	labelview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 3 );
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' ),
		newValue = null;

	assert.throws(
		function() {
			labelview.value( newValue );
		},
		'Trying to set no value fails.'
	);

	newValue = new wb.datamodel.Term( 'de', 'changed label' );

	labelview.value( newValue );

	assert.ok(
		labelview.value().equals( newValue ),
		'Set new value.'
	);

	newValue = new wb.datamodel.Term( 'en', '' );

	labelview.value( newValue );

	assert.ok(
		labelview.value().equals( newValue ),
		'Set another value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
