/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, dv ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'jquery.wikibase.statementview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_statementview' ).each( function () {
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
	var createStatementview = function ( options, $node ) {
		options = $.extend( {
			getAdder: function () {
				return {
					destroy: function () {},
					disable: function () {},
					enable: function () {}
				};
			},
			getReferenceListItemAdapter: function () {
				return wb.tests.getMockListItemAdapter(
					'mytestreferenceview',
					function () {
						this.value = function () {
							return this.options.value;
						};
						this.startEditing = function () {
						};
						this.stopEditing = function () {
						};
						this.enterNewItem = function () {
						};
					}
				);
			},
			buildSnakView: function ( opts, value, $dom ) {
				var _value = value;
				return {
					destroy: function () {},
					option: function () {},
					snak: function () {
						return _value;
					},
					startEditing: function () {
						return $.Deferred().resolve().promise();
					},
					stopEditing: function () {}
				};
			},
			entityIdPlainFormatter: {
				format: function ( entityId ) {
					return $.Deferred().resolve( entityId ).promise();
				}
			},
			guidGenerator: 'I am a ClaimGuidGenerator',
			locked: 'I am a',
			predefined: 'I am a',
			getQualifiersListItemAdapter: function () {
				return wb.tests.getMockListItemAdapter(
					'mytestqualifiersview',
					function () {
						this.value = function () {
							return this.options.value;
						};
					}
				);
			}
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		return $node
			.addClass( 'test_statementview' )
			.statementview( options );
	};

	QUnit.test( 'Create & destroy without value', ( assert ) => {
		var $statementview = createStatementview(),
			statementview = $statementview.data( 'statementview' );

		assert.true(
			statementview instanceof $.wikibase.statementview,
			'Created widget.'
		);

		statementview.destroy();

		assert.strictEqual(
			$statementview.data( 'statementview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Create & destroy with value', ( assert ) => {
		var $statementview = createStatementview( {
				value: new datamodel.Statement(
					new datamodel.Claim(
						new datamodel.PropertyNoValueSnak( 'P1' ),
						null,
						'guid'
					),
					new datamodel.ReferenceList( [ new datamodel.Reference() ] )
				)
			} ),
			statementview = $statementview.data( 'statementview' );

		assert.notStrictEqual(
			statementview,
			undefined,
			'Created widget.'
		);

		statementview.destroy();

		assert.strictEqual(
			$statementview.data( 'statementview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'value after startEditing with value', ( assert ) => {
		var $statementview = createStatementview( {
				value: new datamodel.Statement(
					new datamodel.Claim(
						new datamodel.PropertyNoValueSnak( 'P1' ),
						null,
						'guid'
					),
					new datamodel.ReferenceList( [ new datamodel.Reference() ] )
				)
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			assert.notStrictEqual( statementview.value(), undefined, 'value() should return a value' );
		} );
	} );

	QUnit.test( 'value after startEditing on new statementview', ( assert ) => {
		var $statementview = createStatementview( {
				guidGenerator: {
					newGuid: function () {
						return 'guid';
					}
				}
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			assert.strictEqual( statementview.value(), null, 'value should return null' );
		} );
	} );

	QUnit.test( 'Using the generic tooltip for new claims', ( assert ) => {
		var $statementview = createStatementview(),
			statementview = $statementview.data( 'statementview' );

		var done = assert.async();
		statementview.getHelpMessage().done( ( helpMessage ) => {
			assert.strictEqual( mw.msg( 'wikibase-claimview-snak-new-tooltip' ), helpMessage );
			done();
		} );
	} );

	QUnit.test( 'Using tooltip specific for existing claims', ( assert ) => {
		var $statementview = createStatementview( {
			value: new datamodel.Statement( new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1', new dv.StringValue( 'g' ) )
			) )
		} );

		var statementview = $statementview.data( 'statementview' );
		var done = assert.async();

		statementview.getHelpMessage().done( ( helpMessage ) => {
			assert.strictEqual( mw.msg( 'wikibase-claimview-snak-tooltip', 'P1' ), helpMessage );
			done();
		} );
	} );

	QUnit.test( 'value with empty reference', ( assert ) => {
		var $statementview = createStatementview( {
				value: new datamodel.Statement(
					new datamodel.Claim(
						new datamodel.PropertyNoValueSnak( 'P1' ),
						null,
						'guid'
					),
					new datamodel.ReferenceList( [ ] )
				)
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			statementview._referencesListview.enterNewItem();
			assert.strictEqual( statementview.value(), null, 'value should not return a value' );
		} );
	} );

	QUnit.test( 'wb-new', ( assert ) => {
		var $statementview = createStatementview(),
			statementview = $statementview.data( 'statementview' );

		assert.true( $statementview.hasClass( 'wb-new' ) );

		statementview.value( new datamodel.Statement(
			new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1' ),
				null,
				'guid'
			),
			new datamodel.ReferenceList( [ ] )
		) );

		assert.false( $statementview.hasClass( 'wb-new' ) );
	} );

	QUnit.test( 'wb-rank class', ( assert ) => {
		var $statementview = createStatementview(),
			statementview = $statementview.data( 'statementview' );

		assert.true( $statementview.hasClass( 'wb-normal' ), 'wb-normal class present' );

		statementview.value( new datamodel.Statement(
			new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1' ),
				null,
				'guid'
			),
			new datamodel.ReferenceList( [ ] ),
			datamodel.Statement.RANK.PREFERRED
		) );

		assert.true( $statementview.hasClass( 'wb-preferred' ), 'wb-preferred class added' );
		assert.false( $statementview.hasClass( 'wb-normal' ), 'wb-normal class removed' );
	} );

	QUnit.test( 'fires fireStartEditingHook when starting editing', ( assert ) => {
		var fireSpy = sinon.spy(),
			$statementview = createStatementview( {
				value: new datamodel.Statement(
					new datamodel.Claim(
						new datamodel.PropertyNoValueSnak( 'P1' ),
						null,
						'guid-123'
					),
					new datamodel.ReferenceList( [ new datamodel.Reference() ] )
				),
				fireStartEditingHook: fireSpy
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			assert.strictEqual(
				fireSpy.getCall( 0 ).args[ 0 ],
				'guid-123',
				'Then mw.hook().fire() is called with the guid.'
			);
		} );
	} );

	QUnit.test( 'fires fireStopEditingHook when cancelling the edit', ( assert ) => {
		var fireSpy = sinon.spy(),
			$statementview = createStatementview( {
				value: new datamodel.Statement(
					new datamodel.Claim(
						new datamodel.PropertyNoValueSnak( 'P1' ),
						null,
						'guid-123'
					),
					new datamodel.ReferenceList( [ new datamodel.Reference() ] )
				),
				fireStopEditingHook: fireSpy
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			statementview.stopEditing().done( () => {
				assert.strictEqual(
					fireSpy.getCall( 0 ).args[ 0 ],
					'guid-123',
					'Then mw.hook().fire() is called with the guid.'
				);
			} );
		} );
	} );

	QUnit.test( 'does not fire start/stop editting hooks when creating new statement', ( assert ) => {
		var fireSpy = sinon.spy(),
			$statementview = createStatementview( {
				value: null,
				fireStartEditingHook: fireSpy,
				fireStopEditingHook: fireSpy
			} ),
			statementview = $statementview.data( 'statementview' );

		return statementview.startEditing().done( () => {
			statementview.stopEditing().done( () => {
				assert.true( fireSpy.notCalled );
			} );
		} );
	} );

}( wikibase, dataValues ) );
