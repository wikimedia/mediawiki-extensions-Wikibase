/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb, dv, vf, vv, QUnit ) {
	'use strict';

	var entityStore = {
		get: function() {
			return $.Deferred().resolve( new wb.store.FetchedContent( {
				title: new mw.Title( 'Property:P1' ),
				content: new wb.datamodel.Property(
					'P1',
					'string',
					new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( [
						new wb.datamodel.Term( 'en', 'P1' )
					] ) )
				)
			} ) );
		}
	};

	var valueViewBuilder = new wb.ValueViewBuilder(
		new vv.ExpertStore(),
		new vf.ValueFormatterStore( vf.NullFormatter )
	);

	function createClaimview( value ) {
		var options = {
			// locked, index
			value: value || null,
			entityStore: entityStore,
			valueViewBuilder: valueViewBuilder,
			api: new wb.AbstractedRepoApi( new mw.Api() )
		};

		return $( '<div/>' )
			.addClass( 'test_claimview' )
			.claimview( options );
	}

	QUnit.module( 'jquery.wikibase.claimview' );

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

	QUnit.test( 'Initialize and destroy claimview with value', function( assert ) {
		var $node = createClaimview( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
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
				new wb.datamodel.Claim( new wb.datamodel.PropertyValueSnak( 'p1', new dv.StringValue( 'g' ) ) )
			),
			claimview = $node.data( 'claimview' );

		assertOnMaybePromise(
			assert,
			claimview.options.helpMessage,
			mw.msg( 'wikibase-claimview-snak-tooltip', 'P1' )
		);
	} );

} )( jQuery, mediaWiki, wikibase, dataValues, valueFormatters, jQuery.valueview, QUnit );
