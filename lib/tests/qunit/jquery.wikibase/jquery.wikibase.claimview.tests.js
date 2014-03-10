/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb, dv, QUnit ) {
	'use strict';

	var entityStore = new wb.store.EntityStore();
	entityStore.compile( {
		p1: new wb.store.FetchedContent( {
			title: new mw.Title( 'Property:P1' ),
			content: new wb.Property( {
				id: 'P1',
				type: 'property',
				datatype: 'string',
				label: { en: 'P1' }
			} )
		} )
	} );

	function createClaimview( value ) {
		var options = {
			// locked, index
			value: value || null,
			entityStore: entityStore
		};

		return mw.template('wb-claim', 'new', 'wb-last', '', '')
			.addClass( 'test_claimview' )
			.claimview( options );
	}

	QUnit.module( 'jquery.wikibase.claimview', window.QUnit.newWbEnvironment( {
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var $node = createClaimview(),
			claimview = $node.data( 'claimview' );

		assert.ok(
			claimview !== undefined,
			'Initialized claimview widget.'
		);

		claimview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);
	} );

	function assertOnMaybePromise( assert, maybePromise, expectedVal ) {
		if( maybePromise.done ) {
			maybePromise.done( function( val ) {
				QUnit.start();
				assert.equal( val, expectedVal );
			} );
		} else {
			QUnit.start();
			assert.equal( maybePromise, expectedVal );
		}
	}

	QUnit.asyncTest( 'Using the generic tooltip for new claims', 1, function( assert ) {
		var $node = createClaimview(),
			claimview = $node.data( 'claimview' );

		assertOnMaybePromise(
			assert,
			claimview.options.helpMessage,
			mw.msg( 'wikibase-claimview-snak-new-tooltip' )
		);
	} );

	QUnit.asyncTest( 'Using tooltip specific for existing claims', 1, function( assert ) {
		var $node = createClaimview(
				new wb.Claim( new wb.PropertyValueSnak( 'p1', new dv.StringValue( 'g' ) ) )
			),
			claimview = $node.data( 'claimview' );

		assertOnMaybePromise(
			assert,
			claimview.options.helpMessage,
			mw.msg( 'wikibase-claimview-snak-tooltip', 'P1' )
		);
	} );

} )( jQuery, mediaWiki, wikibase, dataValues, QUnit );
