/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createLabelview = function ( options, $node ) {
		options = $.extend( {
			value: new datamodel.Term( 'en', 'test label' )
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		var $labelview = $node
			.addClass( 'test_labelview' )
			.labelview( options );

		return $labelview;
	};

	QUnit.module( 'jquery.wikibase.labelview', QUnit.newMwEnvironment( {
		afterEach: function () {
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

		assert.true(
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
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'labelviewafterstopediting', function ( event, dropValue ) {
			assert.true(
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

	var TestItem = function ( key ) {
		this._key = key;
	};
	$.extend( TestItem.prototype, {
		equals: function ( other ) {
			return other === this;
		},
		getKey: function () {
			return this._key;
		}
	} );

	var createLanguageSet = function ( languages ) {
		var items = [];
		for ( var i = 0; i < languages.length; i++ ) {
			items.append( new TestItem( languages[ i ] ) );
		}
		return new datamodel.Set( TestItem, 'getKey', items );
	};

	QUnit.test( 'empty label message is set when no label is supplied', function ( assert ) {

		var $labelview = createLabelview( {
			value: new datamodel.Term( 'en', '' ),
			allLanguages: createLanguageSet( wb.WikibaseContentLanguages.getTermLanguages() )
		} );

		assert.strictEqual(
			$labelview.data( 'labelview' ).$text.text(),
			'(wikibase-label-empty)',
			'Expected placeholder to be set'
		);
	} );

	QUnit.test( 'placeholder message is set when no label is supplied and item is in edit mode', function ( assert ) {

		var $labelview = createLabelview( {
			value: new datamodel.Term( 'en', '' ),
			allLanguages: createLanguageSet( wb.WikibaseContentLanguages.getTermLanguages() )
		} );
		var labelview = $labelview.data( 'labelview' );
		labelview.startEditing();

		assert.strictEqual(
			$labelview.find( 'textarea' ).attr( 'placeholder' ),
			'(wikibase-label-edit-placeholder-language-aware: English)',
			'Expected placeholder to be set'
		);
	} );

	QUnit.test( 'special placeholder message is used for edits with language code mul', function ( assert ) {

		var $labelview = createLabelview( {
			value: new datamodel.Term( 'mul', '' ),
			allLanguages: createLanguageSet( wb.WikibaseContentLanguages.getTermLanguages() )
		} );
		var labelview = $labelview.data( 'labelview' );
		labelview.startEditing();

		assert.strictEqual(
			$labelview.find( 'textarea' ).attr( 'placeholder' ),
			'(wikibase-label-edit-placeholder-mul)',
			'Expected placeholder to be set'
		);
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $labelview = createLabelview(),
			labelview = $labelview.data( 'labelview' );

		$labelview
		.on( 'labelviewtoggleerror', function ( event, error ) {
			assert.true(
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

		newValue = new datamodel.Term( 'de', 'changed label' );

		labelview.value( newValue );

		assert.strictEqual(
			labelview.value().equals( newValue ),
			true,
			'Set new value.'
		);

		newValue = new datamodel.Term( 'en', '' );

		labelview.value( newValue );

		assert.strictEqual(
			labelview.value().equals( newValue ),
			true,
			'Set another value.'
		);
	} );

}( wikibase ) );
