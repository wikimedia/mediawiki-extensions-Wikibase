/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createLabelview = function ( options, $node ) {
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
		teardown: function () {
			$( '.test_labelview' ).each( function () {
				var $labelview = $( this ),
					labelview = $labelview.data( 'labelview' );

				if ( labelview ) {
					labelview.destroy();
				}

				$labelview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.throws(
			function () {
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

		assert.strictEqual(
			$labelview.data( 'labelview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $labelview = createLabelview(),
			labelview = $labelview.data( 'labelview' );

		$labelview
		.on( 'labelviewafterstartediting', function ( event ) {
			assert.ok(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'labelviewafterstopediting', function ( event, dropValue ) {
			assert.ok(
				true,
				'Stopped edit mode.'
			);
		} );

		labelview.startEditing();

		assert.strictEqual(
			labelview.$text.find( 'textarea' ).length,
			1,
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

	QUnit.test( 'setError()', function ( assert ) {
		var $labelview = createLabelview(),
			labelview = $labelview.data( 'labelview' );

		$labelview
		.on( 'labelviewtoggleerror', function ( event, error ) {
			assert.ok(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		labelview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $labelview = createLabelview(),
			labelview = $labelview.data( 'labelview' ),
			newValue = null;

		assert.throws(
			function () {
				labelview.value( newValue );
			},
			'Trying to set no value fails.'
		);

		newValue = new wb.datamodel.Term( 'de', 'changed label' );

		labelview.value( newValue );

		assert.strictEqual(
			labelview.value().equals( newValue ),
			true,
			'Set new value.'
		);

		newValue = new wb.datamodel.Term( 'en', '' );

		labelview.value( newValue );

		assert.strictEqual(
			labelview.value().equals( newValue ),
			true,
			'Set another value.'
		);
	} );

}( wikibase ) );
