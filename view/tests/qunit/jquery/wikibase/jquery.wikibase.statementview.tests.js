/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $, mw, wb, dv, QUnit, sinon ) {
'use strict';

QUnit.module( 'jquery.wikibase.statementview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_statementview' ).each( function() {
			var $statementview = $( this ),
				statementview = $statementview.data( 'statementview' );

			if ( statementview ) {
				statementview.destroy();
			}

			$statementview.remove();
		} );
	}
} ) );

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementview = function( options, $node ) {
	options = $.extend( {
		buildReferenceListItemAdapter: function() {
			return wb.tests.getMockListItemAdapter(
				'mytestreferenceview',
				function() {
					this.value = function() {
						return this.options.value;
					};
					this.startEditing = function() {
					};
					this.isValid = function() {
						return true;
					};
				}
			);
		},
		buildSnakView: function( options, value, $dom ) {
			var _value = value;
			return {
				destroy: function() {},
				isInitialValue: function() {
					return true;
				},
				isValid: function() {
					return true;
				},
				option: function() {},
				snak: function() {
					return _value;
				},
				startEditing: function() {
					return $.Deferred().resolve().promise();
				},
				stopEditing: function() {}
			};
		},
		claimsChanger: 'I am a ClaimsChanger',
		entityIdPlainFormatter: {
			format: function( entityId ) {
				return $.Deferred().resolve( entityId ).promise();
			}
		},
		guidGenerator: 'I am a ClaimGuidGenerator',
		locked: 'I am a',
		predefined: 'I am a',
		qualifiersListItemAdapter: wb.tests.getMockListItemAdapter(
			'mytestqualifiersview',
			function() {
				this.value = function() {
					return this.options.value;
				};
			}
		)
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	return $node
		.addClass( 'test_statementview' )
		.statementview( options );
};

QUnit.test( 'Create & destroy without value', function( assert ) {
	assert.expect( 2 );
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
	assert.expect( 2 );
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
	assert.expect( 1 );
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

	QUnit.stop();
	statementview.startEditing().done( function() {
		QUnit.start();
		assert.ok( statementview.isValid(), 'isValid should return true' );
	} );
} );

QUnit.test( 'isValid on new statementview is false', function( assert ) {
	assert.expect( 1 );
	var $statementview = createStatementview(),
		statementview = $statementview.data( 'statementview' );

	QUnit.stop();
	statementview.startEditing().done( function() {
		QUnit.start();
		assert.ok( !statementview.isValid(), 'isValid should return false' );
	} );
} );

QUnit.test( 'Using the generic tooltip for new claims', 1, function( assert ) {
	var $statementview = createStatementview(),
		statementview = $statementview.data( 'statementview' );

	var done = assert.async();
	statementview.getHelpMessage().done( function( helpMessage ) {
		assert.equal( mw.msg( 'wikibase-claimview-snak-new-tooltip' ), helpMessage );
		done();
	} );
} );

QUnit.test( 'Using tooltip specific for existing claims', 1, function( assert ) {
	var $statementview = createStatementview( {
		value: new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1', new dv.StringValue( 'g' ) )
			) )
		} );

	var statementview = $statementview.data( 'statementview' );
	var done = assert.async();

	statementview.getHelpMessage().done( function( helpMessage ) {
		assert.equal( mw.msg( 'wikibase-claimview-snak-tooltip', 'P1' ), helpMessage );
		done();
	} );
} );

QUnit.test( 'value with empty reference', function( assert ) {
	assert.expect( 1 );
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

	QUnit.stop();
	statementview.startEditing().done( function() {
		QUnit.start();
		statementview._addReference( null );
		assert.ok( statementview.value(), 'value should return a value' );
	} );
} );

QUnit.test( 'performs correct claimsChanger call', function( assert ) {
	assert.expect( 3 );
	var guid = 'GUID',
		snak = new wb.datamodel.PropertyNoValueSnak( 'P1' ),
		setStatement = sinon.spy( function() {
			return $.Deferred().resolve().promise();
		} ),
		$statementview = createStatementview( {
			claimsChanger: {
				setStatement: setStatement
			},
			guidGenerator: {
				newGuid: function() { return guid; }
			}
		} ),
		statementview = $statementview.data( 'statementview' );

	QUnit.stop();
	statementview.startEditing().then( function() {
		QUnit.start();
		assert.ok( statementview.isInEditMode(), 'should be in edit mode after starting editing' );

		// Change main snak
		statementview._mainSnakSnakView.snak = function() {
			return snak;
		};
		statementview._mainSnakSnakView.isInitialValue = function() {
			return false;
		};

		QUnit.stop();
		return statementview.stopEditing( false );
	} ).then( function() {
		QUnit.start();
		assert.ok( !statementview.isInEditMode(), 'should not be in edit mode after stopping editing' );
		sinon.assert.calledWith(
			setStatement,
			new wb.datamodel.Statement( new wb.datamodel.Claim( snak, null, guid ) )
		);
	} );
} );

}( jQuery, mediaWiki, wikibase, dataValues, QUnit, sinon ) );
