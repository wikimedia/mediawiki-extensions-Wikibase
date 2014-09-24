/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb, vv, vf, QUnit ) {
	'use strict';

	// We need an entity store for the instances of jquery.wikibase.referenceview
	// and jquery.wikibase.snakview created by jquery.wikibase.referenceview.
	var entityStore = {
		get: function() {
			return $.Deferred().resolve( new wb.store.FetchedContent( {
				title: new mw.Title( 'Property:P1' ),
				content: new wb.datamodel.Property( {
					id: 'P1',
					type: 'property',
					datatype: 'string',
					label: { en: 'P1' }
				} )
			} ) );
		}
	};

	var valueViewBuilder = new wb.ValueViewBuilder(
		new vv.ExpertStore(),
		new vf.ValueFormatterStore( vf.NullFormatter )
	);

	/**
	 * Generates a referenceview widget suitable for testing.
	 *
	 * @param {string} [statementGuid]
	 * @param {Object} [additionalOptions]
	 * @return {jQuery}
	 */
	function createReferenceview( statementGuid, additionalOptions ) {
		var options = $.extend( additionalOptions, {
			statementGuid: statementGuid,
			entityStore: entityStore,
			valueViewBuilder: valueViewBuilder,
			referencesChanger: 'referencesChanger'
		} );

		return $( '<div/>' )
			.addClass( 'test_referenceview' )
			.referenceview( options );
	}

	QUnit.module( 'jquery.wikibase.referenceview', window.QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_referenceview' ).each( function( i, node ) {
				var $node = $( node ),
					referenceview = $node.data( 'referenceview' );

				if( referenceview ) {
					referenceview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var $node = createReferenceview( 'testGuid' ),
			referenceview = $node.data( 'referenceview' );

		assert.ok(
			referenceview !== undefined,
			'Initialized referenceview widget.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no reference.'
		);

		assert.strictEqual(
			referenceview.isValid(),
			true,
			'Referenceview is valid.'
		);

		assert.strictEqual(
			referenceview.isInitialValue(),
			true,
			'Referenceview holds initial value.'
		);

		assert.strictEqual(
			referenceview.isInEditMode(),
			false,
			'Referenceview is not in edit mode.'
		);

		assert.ok(
			referenceview.$listview.data( 'listview' ),
			'Initialized listview.'
		);

		referenceview.destroy();

		assert.strictEqual(
			referenceview.$listview, null,
			'Destroyed listview.'
		);
	} );

	QUnit.test( 'is initialized with a value', function( assert ) {
		var $node = createReferenceview( 'testGuid', {
				value: new wb.datamodel.Reference(
					new wb.datamodel.PropertyNoValueSnak( 'P1' )
				)
			} ),
			referenceview = $node.data( 'referenceview' );

		assert.strictEqual(
			referenceview.value().getSnaks().length,
			1,
			'Referenceview contains a reference.'
		);
	} );

	QUnit.test( 'allows to enter new item', function( assert ) {
		var $node = createReferenceview( 'testGuid' ),
			referenceview = $node.data( 'referenceview' );

		referenceview.enterNewItem();

		assert.strictEqual(
			referenceview.isInEditMode(),
			true,
			'Referenceview is in edit mode.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no reference.'
		);

		assert.strictEqual(
			referenceview.isValid(),
			false,
			'Referenceview is invalid.'
		);
	} );

	QUnit.test( 'allows to stop editing', function( assert ) {
		var $node = createReferenceview( 'testGuid' ),
			referenceview = $node.data( 'referenceview' );

		referenceview.enterNewItem();
		referenceview.stopEditing( true );

		assert.strictEqual(
			referenceview.isInEditMode(),
			false,
			'Referenceview is not in edit mode.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no reference.'
		);

		assert.strictEqual(
			referenceview.isValid(),
			true,
			'Referenceview is valid.'
		);
	} );

	QUnit.test( 'recognizes initial value', function( assert ) {
		var $node = createReferenceview( 'testGuid', {
				value: new wb.datamodel.Reference( new wb.datamodel.SnakList( [
					new wb.datamodel.PropertyNoValueSnak( 'P1' )
				] ) )
			} ),
			referenceview = $node.data( 'referenceview' );

		assert.strictEqual(
			referenceview.isInitialValue(),
			true,
			'Referenceview has initial value.'
		);
	} );

} )( jQuery, mediaWiki, wikibase, jQuery.valueview, valueFormatters, QUnit );
