/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, jQuery, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createDescriptionview = function( options, $node ) {
	options = $.extend( {
		descriptionsChanger: {
			setDescription: function () { return $.Deferred().resolve(); }
		},
		value: {
			language: 'en',
			description: 'test description'
		}
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $descriptionview = $node
		.addClass( 'test_descriptionview' )
		.descriptionview( options );

	$descriptionview.data( 'descriptionview' )._save = function() {
		return $.Deferred().resolve( {
			entity: {
				lastrevid: 'i am a revision id'
			}
		} ).promise();
	};

	return $descriptionview;
};

QUnit.module( 'jquery.wikibase.descriptionview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_descriptionview' ).each( function() {
			var $descriptionview = $( this ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if( descriptionview ) {
				descriptionview.destroy();
			}

			$descriptionview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createDescriptionview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	assert.ok(
		descriptionview !== 'undefined',
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
		descriptionview.$text.find( 'input' ).length === 1,
		'Generated input element.'
	);

	descriptionview.startEditing(); // should not trigger event
	descriptionview.stopEditing( true );
	descriptionview.stopEditing( true ); // should not trigger event
	descriptionview.stopEditing(); // should not trigger event
	descriptionview.startEditing();

	descriptionview.$text.find( 'input' ).val( '' );

	descriptionview.stopEditing();
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	descriptionview.startEditing();

	assert.ok(
		descriptionview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	descriptionview.$text.find( 'input' ).val( 'changed' );

	assert.ok(
		!descriptionview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	descriptionview.$text.find( 'input' ).val( 'test description' );

	assert.ok(
		descriptionview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
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
	var $descriptionview = createDescriptionview(),
		descriptionview = $descriptionview.data( 'descriptionview' );

	assert.throws(
		function() {
			descriptionview.value( null );
		},
		'Trying to set no value fails.'
	);

	descriptionview.value( {
		language: 'de',
		description: 'changed description'
	} );

	assert.ok(
		descriptionview.value().language === 'de'
			&& descriptionview.value().description === 'changed description',
		'Set new value.'
	);

	descriptionview.value( {
		language: 'en',
		description: null
	} );

	assert.ok(
		descriptionview.value().language === 'en'
			&& descriptionview.value().description === null,
		'Set another value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
