/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createLabelview = function( options, $node ) {
	options = $.extend( {
		entityId: 'i am an EntityId',
		labelsChanger: 'i am a LabelsChanger',
		value: {
			language: 'en',
			label: 'test label'
		}
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $labelview = $node
		.addClass( 'test_labelview' )
		.labelview( options );

	$labelview.data( 'labelview' )._save = function() {
		return $.Deferred().resolve( {
			entity: {
				lastrevid: 'i am a revision id'
			}
		} ).promise();
	};

	return $labelview;
};

QUnit.module( 'jquery.wikibase.labelview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_labelview' ).each( function() {
			var $labelview = $( this ),
				labelview = $labelview.data( 'labelview' );

			if( labelview ) {
				labelview.destroy();
			}

			$labelview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createLabelview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	assert.ok(
		labelview !== 'undefined',
		'Created widget.'
	);

	labelview.destroy();

	assert.ok(
		$labelview.data( 'labelview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 5, function( assert ) {
	var $labelview = createLabelview( {
			labelsChanger: {
				setLabel: function () { return $.Deferred().resolve(); }
			}
		} ),
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
		labelview.$text.find( 'input' ).length === 1,
		'Generated input element.'
	);

	labelview.startEditing(); // should not trigger event
	labelview.stopEditing( true );
	labelview.stopEditing( true ); // should not trigger event
	labelview.stopEditing(); // should not trigger event
	labelview.startEditing();

	labelview.$text.find( 'input' ).val( '' );

	labelview.stopEditing();
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	labelview.startEditing();

	assert.ok(
		labelview.isInitialValue(),
		'Verified isInitialValue() returning true.'
	);

	labelview.$text.find( 'input' ).val( 'changed' );

	assert.ok(
		!labelview.isInitialValue(),
		'Verified isInitialValue() returning false after changing value.'
	);

	labelview.$text.find( 'input' ).val( 'test label' );

	assert.ok(
		labelview.isInitialValue(),
		'Verified isInitialValue() returning true after resetting to initial value.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
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
	var $labelview = createLabelview(),
		labelview = $labelview.data( 'labelview' );

	assert.throws(
		function() {
			labelview.value( null );
		},
		'Trying to set no value fails.'
	);

	labelview.value( {
		language: 'de',
		label: 'changed label'
	} );

	assert.ok(
		labelview.value().language === 'de'
			&& labelview.value().label === 'changed label',
		'Set new value.'
	);

	labelview.value( {
		language: 'en',
		label: null
	} );

	assert.ok(
		labelview.value().language === 'en'
			&& labelview.value().label === null,
		'Set another value.'
	);
} );

}( jQuery, QUnit ) );
