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
	var createDescriptionview = function ( options, $node ) {
		options = Object.assign( {
			value: new datamodel.Term( 'en', 'test description' )
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		var $descriptionview = $node
			.addClass( 'test_descriptionview' )
			.descriptionview( options );

		return $descriptionview;
	};

	QUnit.module( 'jquery.wikibase.descriptionview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_descriptionview' ).each( function () {
				var $descriptionview = $( this ),
					descriptionview = $descriptionview.data( 'descriptionview' );

				if ( descriptionview ) {
					descriptionview.destroy();
				}

				$descriptionview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', ( assert ) => {
		assert.throws(
			() => {
				createDescriptionview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $descriptionview = createDescriptionview(),
			descriptionview = $descriptionview.data( 'descriptionview' );

		assert.true(
			descriptionview instanceof $.wikibase.descriptionview,
			'Created widget.'
		);

		descriptionview.destroy();

		assert.strictEqual(
			$descriptionview.data( 'descriptionview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', ( assert ) => {
		var $descriptionview = createDescriptionview(),
			descriptionview = $descriptionview.data( 'descriptionview' );

		$descriptionview
		.on( 'descriptionviewafterstartediting', ( event ) => {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'descriptionviewafterstopediting', ( event, dropValue ) => {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		descriptionview.startEditing();

		var $textarea = descriptionview.$text.find( 'textarea' );
		assert.strictEqual(
			$textarea.length,
			1,
			'Generated textarea element.'
		);
		assert.strictEqual(
			$textarea.get( 0 ).readOnly,
			false,
			'textarea is not read only.'
		);
		assert.strictEqual(
			$textarea.attr( 'aria-label' ),
			undefined,
			'textarea has no aria-label.'
		);

		descriptionview.startEditing(); // should not trigger event
		descriptionview.stopEditing( true );
		descriptionview.stopEditing( true ); // should not trigger event
		descriptionview.stopEditing(); // should not trigger event
		descriptionview.startEditing();

		descriptionview.$text.find( 'textarea' ).val( '' );

		descriptionview.stopEditing();
	} );

	QUnit.test( 'read only mode with accessibility label', ( assert ) => {
		var accessibilityLabel = 'a11y-label-text',
			$descriptionview = createDescriptionview( {
				readOnly: true,
				accessibilityLabel: accessibilityLabel
			} ),
			descriptionview = $descriptionview.data( 'descriptionview' );

		descriptionview.startEditing();

		var $textarea = descriptionview.$text.find( 'textarea' );
		assert.strictEqual(
			$textarea.length,
			1,
			'Generated textarea element.'
		);
		assert.strictEqual(
			$textarea.get( 0 ).readOnly,
			true,
			'textarea is read only.'
		);
		assert.strictEqual(
			$textarea.attr( 'aria-label' ),
			accessibilityLabel,
			'textarea has the aria-label set.'
		);
	} );

	QUnit.test( 'setError()', ( assert ) => {
		var $descriptionview = createDescriptionview(),
			descriptionview = $descriptionview.data( 'descriptionview' );

		$descriptionview
		.on( 'descriptionviewtoggleerror', ( event, error ) => {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		descriptionview.setError();
	} );

	QUnit.test( 'value()', ( assert ) => {
		var $descriptionview = createDescriptionview(),
			descriptionview = $descriptionview.data( 'descriptionview' ),
			newValue = null;

		assert.throws(
			() => {
				descriptionview.value( newValue );
			},
			'Trying to set no value fails.'
		);

		newValue = new datamodel.Term( 'de', 'changed description' );

		descriptionview.value( newValue );

		assert.strictEqual(
			descriptionview.value().equals( newValue ),
			true,
			'Set new value.'
		);

		newValue = new datamodel.Term( 'en', '' );

		descriptionview.value( newValue );

		assert.strictEqual(
			descriptionview.value().equals( newValue ),
			true,
			'Set another value.'
		);
	} );

	QUnit.test( 'shows N/A placeholder for mul', ( assert ) => {
		var $descriptionview = createDescriptionview( {
				value: new datamodel.Term( 'mul', '' )
			} ),
			descriptionview = $descriptionview.data( 'descriptionview' );

		assert.strictEqual(
			descriptionview.$text.text(),
			'(wikibase-description-not-applicable)',
			'Shows "not applicable" placeholder.'
		);
	} );

}() );
