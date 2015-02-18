/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( $, mw, wb, dv, QUnit, sinon ) {
'use strict';

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

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementview = function( options, $node ) {
	options = $.extend( {
		entityStore: entityStore,
		valueViewBuilder: 'i am a valueview builder',
		claimsChanger: 'I am a ClaimsChanger',
		entityChangersFactory: {
			getReferencesChanger: function() {
				return 'I am a ReferencesChanger';
			}
		},
		dataTypeStore: 'I am a DataTypeStore',
		guidGenerator: 'I am a ClaimGuidGenerator'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	return $node
		.addClass( 'test_statementview' )
		.statementview( options );
};

/**
 * @param {Object} assert QUnit.assert
 * @param {*} maybePromise
 * @param {*} expectedVal
 */
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

QUnit.test( 'Create & destroy without value', function( assert ) {
	var $statementview = createStatementview(),
		statementview = $statementview.data( 'statementview' );

	assert.ok(
		statementview instanceof $.wikibase.statementview,
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
			value: new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P1' ),
					null,
					'guid'
				),
				new wb.datamodel.ReferenceList( [ new wb.datamodel.Reference() ] )
			)
		} ),
		statementview = $statementview.data( 'statementview' );

	assert.ok(
		statementview !== undefined,
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
			value: new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P1' ),
					null,
					'guid'
				),
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
			value: new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P1' ),
					null,
					'guid'
				),
				new wb.datamodel.ReferenceList( [ reference ] )
			),
			entityChangersFactory: {
				getReferencesChanger: function() {
					return referencesChanger;
				}
			}
		} ),
		statementview = $statementview.data( 'statementview' );

	statementview.remove(
		$statementview.find( ':wikibase-referenceview' ).data( 'referenceview' )
	);
	sinon.assert.calledWith( referencesChanger.removeReference, 'guid', reference );
} );

QUnit.asyncTest( 'Using the generic tooltip for new claims', 1, function( assert ) {
	var $statementview = createStatementview(),
		statementview = $statementview.data( 'statementview' );

	assertOnMaybePromise(
		assert,
		statementview.options.helpMessage,
		mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	);
} );

QUnit.asyncTest( 'Using tooltip specific for existing claims', 1, function( assert ) {
	var $statementview = createStatementview( {
		value: new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1', new dv.StringValue( 'g' ) )
			) )
		} );

	var statementview = $statementview.data( 'statementview' );

	assertOnMaybePromise(
		assert,
		statementview.options.helpMessage,
		mw.msg( 'wikibase-claimview-snak-tooltip', 'P1' )
	);
} );

QUnit.test( 'value with empty reference', function( assert ) {
	var $statementview = createStatementview( {
			value: new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P1' ),
					null,
					'guid'
				),
				new wb.datamodel.ReferenceList( [ ] )
			)
		} ),
		statementview = $statementview.data( 'statementview' );

	statementview._addReference( null );

	assert.ok( statementview.value(), 'value should return a value' );
} );

QUnit.test( 'performs correct claimsChanger call', function( assert ) {
	var guid = 'GUID',
		snak = new wb.datamodel.PropertyNoValueSnak( 'P1' ),
		setStatement = sinon.spy( function() {
			return $.Deferred().resolve().promise();
		} ),
		$statementview = createStatementview( {
			claimsChanger: {
				setStatement: setStatement
			},
			dataTypeStore: {
				getDataType: function() { return null; }
			},
			guidGenerator: {
				newGuid: function() { return guid; }
			}
		} ),
		statementview = $statementview.data( 'statementview' );

	statementview.startEditing();

	statementview.$mainSnak.find( ':wikibase-entityselector' ).data( 'wikibase-entityselector' )._select( { id: 'P1' } );
	statementview.$mainSnak.find( ':wikibase-snaktypeselector' ).data( 'snaktypeselector' ).snakType( 'novalue' );

	QUnit.stop();

	statementview.stopEditing( false ).then( function() {
		QUnit.start();
		sinon.assert.calledWith(
			setStatement,
			new wb.datamodel.Statement( new wb.datamodel.Claim( snak, null, guid ) )
		);
	} );
} );

}( jQuery, mediaWiki, wikibase, dataValues, QUnit, sinon ) );
