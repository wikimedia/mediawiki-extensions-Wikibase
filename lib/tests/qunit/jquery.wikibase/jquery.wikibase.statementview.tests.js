/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

( function( $, wb, QUnit, sinon ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementview = function( options, $node ) {
	options = $.extend( {
		entityChangersFactory: {
			getReferencesChanger: function() {
				return 'i am a references changer';
			}
		},
		entityStore: {
			get: function () { return $.Deferred().resolve().promise(); }
		},
		valueViewBuilder: 'i am a valueview builder',
		api: 'i am an api'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $statementview = $node
		.addClass( 'test_statementview' )
		.statementview( options );

	return $statementview;
};

QUnit.module( 'jquery.wikibase.statementview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_statementview' ).each( function() {
			var $statementview = $( this ),
				statementview = $statementview.data( 'statementview' );

			if( statementview ) {
				statementview.destroy();
			}

			$statementview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy without value', function( assert ) {
	var $statementview = createStatementview(),
		statementview = $statementview.data( 'statementview' );

	assert.ok(
		statementview !== 'undefined',
		'Created widget.'
	);

	statementview.destroy();

	assert.ok(
		$statementview.data( 'statementview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'Create & destroy with value', function( assert ) {
	var $statementview = createStatementview( {
			value: new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid' ),
				new wb.datamodel.ReferenceList( [ new wb.datamodel.Reference() ] )
			)
		} ),
		statementview = $statementview.data( 'statementview' );

	assert.ok(
		statementview !== 'undefined',
		'Created widget.'
	);

	statementview.destroy();

	assert.ok(
		$statementview.data( 'statementview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'isValid', function( assert ) {
	var $statementview = createStatementview( {
			value: new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid' ),
				new wb.datamodel.ReferenceList( [ new wb.datamodel.Reference() ] )
			)
		} ),
		statementview = $statementview.data( 'statementview' );

	assert.ok( statementview.isValid(), 'isValid should return true' );
} );

QUnit.test( 'remove', function( assert ) {
	var referencesChanger = {
			removeReference: sinon.spy( function() { return $.Deferred().resolve().promise(); } )
		},
		reference = new wb.datamodel.Reference(),
		$statementview = createStatementview( {
			value: new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'guid' ),
				new wb.datamodel.ReferenceList( [ reference ] )
			),
			entityChangersFactory: {
				getReferencesChanger: function() {
					return referencesChanger;
				}
			}
		} ),
		statementview = $statementview.data( 'statementview' );

	statementview.remove( $statementview.find( ':wikibase-referenceview' ).data( 'referenceview' ) );
	sinon.assert.calledWith( referencesChanger.removeReference, 'guid', reference );
} );

}( jQuery, wikibase, QUnit, sinon ) );
