/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, dv, QUnit ) {
	'use strict';

	var snakLists = [
		new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyValueSnak( 'p1', new dv.StringValue( 'a' ) ),
			new wb.datamodel.PropertyValueSnak( 'p2', new dv.StringValue( 'b' ) ),
			new wb.datamodel.PropertyValueSnak( 'p3', new dv.StringValue( 'c' ) )
		] ),
		new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyValueSnak( 'p4', new dv.StringValue( 'd' ) )
		] )
	];

	var listItemAdapter = wb.tests.getMockListItemAdapter( 'snakview', function() {
		var _value = this.options.value;
		this.options.locked = this.options.locked || {};
		this.snak = function( value ) {
			if ( arguments.length ) {
				_value = value;
			}
			return _value;
		};
		this.isValid = function() {};
		this.startEditing = function() {
			this._trigger( 'change' );
			this._trigger( 'afterstartediting' );
		};
		this.stopEditing = function() {};

		this.showPropertyLabel = function() {
			this._propertyLabelVisible = true;
		};
		this.hidePropertyLabel = function() {
			this._propertyLabelVisible = false;
		};
	} );

	/**
	 * Generates a snaklistview widget suitable for testing.
	 *
	 * @param {wikibase.datamodel.SnakList} [value]
	 * @param {Object} [additionalOptions]
	 * @return {jQuery}
	 */
	function createSnaklistview( value, additionalOptions ) {
		var options = $.extend( additionalOptions, {
			value: value || undefined,
			listItemAdapter: listItemAdapter
		} );

		return $( '<div/>' )
			.addClass( 'test_snaklistview' )
			.snaklistview( options );
	}

	/**
	 * Sets a snak list on a given snaklistview retaining the initial snak list (since it gets
	 * overwritten by using value() to set a snak list).
	 *
	 * @param {jQuery.wikibase.snaklistview} snaklistview
	 * @param {wikibase.datamodel.SnakList} value
	 * @return {jQuery.wikibase.snaklistview}
	 */
	function setValueKeepingInitial( snaklistview, value ) {
		var initialValue = snaklistview.option( 'value' );

		snaklistview.value( value );
		snaklistview.options.value = initialValue;

		return snaklistview;
	}

	QUnit.module( 'jquery.wikibase.snaklistview', window.QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_snaklistview' ).each( function( i, node ) {
				var $node = $( node ),
					snaklistview = $node.data( 'snaklistview' );

				if ( snaklistview ) {
					snaklistview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		assert.expect( 8 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.ok(
			snaklistview !== undefined,
			'Initialized snaklistview widget.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview contains no snaks.'
		);

		assert.strictEqual(
			snaklistview.isValid(),
			true,
			'Snaklistview is valid.'
		);

		assert.strictEqual(
			snaklistview.isInitialValue(),
			true,
			'Snaklistview holds initial value.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Snaklistview is not in edit mode.'
		);

		assert.ok(
			snaklistview.$listview.data( 'listview' ),
			'Initialized listview.'
		);

		snaklistview.destroy();

		assert.strictEqual(
			snaklistview.$listview,
			null,
			'Destroyed listview.'
		);

		assert.throws(
			function() {
				createSnaklistview( {
					value: null
				} );
			},
			'Throwing error when trying to instantiate widget without a proper value.'
		);
	} );

	QUnit.test( 'Setting and getting value while not in edit mode', function( assert ) {
		assert.expect( 5 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		snaklistview.value( snakLists[0] );

		assert.ok(
			snaklistview.value().equals( snakLists[0] ),
			'Set snak list.'
		);

		assert.ok(
			!snaklistview.isInEditMode(),
			'Snaklistview is not in edit mode.'
		);

		snaklistview.value( snakLists[1] );

		assert.ok(
			snaklistview.value().equals( snakLists[1] ),
			'Overwrote snak list.'
		);

		snaklistview.value( new wb.datamodel.SnakList() );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Set empty snak list.'
		);
	} );

	QUnit.test( 'Setting value while in edit mode', function( assert ) {
		assert.expect( 8 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		snaklistview.startEditing();

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		snaklistview.value( snakLists[0] );

		assert.ok(
			snaklistview.value().equals( snakLists[0] ),
			'Set snak list.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		snaklistview.value( snakLists[1] );

		assert.ok(
			snaklistview.value().equals( snakLists[1] ),
			'Overwrote snak list.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		snaklistview.value( new wb.datamodel.SnakList() );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Set empty snak list.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);
	} );

	QUnit.test( 'isInitialValue()', function( assert ) {
		assert.expect( 7 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.strictEqual(
			snaklistview.isInitialValue(),
			true,
			'Empty snak list has initial value.'
		);

		snaklistview = setValueKeepingInitial( snaklistview, snakLists[0] );

		assert.strictEqual(
			snaklistview.isInitialValue(),
			false,
			'snaklistview does not have the initial value after setting a value.'
		);

		snaklistview = setValueKeepingInitial( snaklistview, new wb.datamodel.SnakList() );

		assert.ok(
			snaklistview.isInitialValue(),
			'snaklistview has initial value again after resetting to an empty snak list.'
		);

		snaklistview.destroy();
		$node.remove();

		$node = createSnaklistview( snakLists[0] );
		snaklistview = $node.data( 'snaklistview' );

		assert.strictEqual(
			snaklistview.isInitialValue(),
			true,
			'Snak list initialized with a snak list has initial value.'
		);

		snaklistview = setValueKeepingInitial( snaklistview, snakLists[1] );

		assert.strictEqual(
			snaklistview.isInitialValue(),
			false,
			'snaklistview does not have the initial value after overwriting its value.'
		);

		snaklistview = setValueKeepingInitial( snaklistview, new wb.datamodel.SnakList() );

		assert.ok(
			!snaklistview.isInitialValue(),
			'snaklistview does not have the initial value after setting value to an empty snak list.'
		);

		snaklistview = setValueKeepingInitial(
			snaklistview, new wb.datamodel.SnakList( snakLists[0].toArray() )
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'snaklistview has initial value again after setting to a copy of the initial snak list.'
		);
	} );

	QUnit.test( 'Basic start and stop editing', 6, function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop();

		$node.on( 'snaklistviewafterstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "afterstartediting" event.'
			);

			QUnit.start();
		} );

		snaklistview.startEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Entered edit mode.'
		);

		// Should not trigger any events since in edit mode already:
		snaklistview.startEditing();

		QUnit.stop( 2 );

		$node.on( 'snaklistviewstopediting', function( e ) {
			assert.ok(
				true,
				'Triggered "stopediting" event.'
			);

			QUnit.start();
		} );

		$node.on( 'snaklistviewafterstopediting', function( e ) {
			assert.ok(
				true,
				'Triggered "afterstopediting" event.'
			);

			QUnit.start();
		} );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is still empty.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();
	} );

	QUnit.test( 'Basic start and stop editing of filled snaklistview', 6, function( assert ) {
		var $node = createSnaklistview( snakLists[0] ),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop();

		$node.on( 'snaklistviewafterstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "afterstartediting" event.'
			);

			QUnit.start();
		} );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( $( 'body' ) );

		snaklistview.startEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Entered edit mode.'
		);

		// Should not trigger any events since in edit mode already:
		snaklistview.startEditing();

		QUnit.stop( 2 );

		$node.on( 'snaklistviewstopediting', function( e ) {
			assert.ok(
				true,
				'Triggered "stopediting" event.'
			);

			QUnit.start();
		} );

		$node.on( 'snaklistviewafterstopediting', function( e ) {
			assert.ok(
				true,
				'Triggered "afterstopediting" event.'
			);

			QUnit.start();
		} );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.isInitialValue(),
			true,
			'Snaklistview still features initial value.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();

		$node.remove();
	} );

	QUnit.test( 'enterNewItem()', function( assert ) {
		assert.expect( 5 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop( 2 );

		$node.on( 'snaklistviewafterstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "afterstartediting" event.'
			);
			QUnit.start();
		} );

		$node.on( 'snaklistviewchange', function( e ) {
			assert.ok(
				true,
				'Triggered "change" event.'
			);
			QUnit.start();
		} );

		snaklistview.enterNewItem();

		assert.ok(
			snaklistview.isInEditMode(),
			'Verified snaklistview being in edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Snaklistview still features its initial value ignoring pending empty value.'
		);

		assert.ok(
			!snaklistview.isValid(),
			'Snaklistview is not valid due to pending value.'
		);
	} );

	QUnit.test( 'cancelEditing()', function( assert ) {
		assert.expect( 1 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		// Since cancelEditing is just a short-cut, there is no need for particular testing expect
		// for verifying the actual short-cut behaviour.
		snaklistview.stopEditing = function( dropValue ) {
			assert.strictEqual(
				dropValue,
				true,
				'Called stopEditing with dropValue flag set to TRUE.'
			);
		};

		snaklistview.startEditing();
		snaklistview.cancelEditing();
	} );

	QUnit.test( 'Stopping edit mode dropping value', function( assert ) {
		assert.expect( 6 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( $( 'body' ) );

		// Start with empty snaklistview, set a snak list and stop edit mode:
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[0] );

		snaklistview.stopEditing( true );

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Verified reset to initial value.'
		);

		// Start with a filled snaklistview, set a snak list and stop edit mode:
		snaklistview.value( snakLists[0] );

		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[1] );

		snaklistview.stopEditing( true );

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Verified reset to initial value.'
		);

		// Set an empty snak list and stop edit mode.
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, new wb.datamodel.SnakList() );

		snaklistview.stopEditing( true );

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Verified reset to initial value.'
		);

		$node.remove();
	} );

	QUnit.test( 'Stopping edit mode retaining value', function( assert ) {
		assert.expect( 9 );
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( $( 'body' ) );

		// Start with empty snaklistview, set a snak list and stop edit mode:
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[0] );

		snaklistview.stopEditing();

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			!snaklistview.isInitialValue(),
			'Snaklistview\'s value changed.'
		);

		assert.ok(
			snakLists[0].equals( snaklistview.value() ),
			'Verified new value.'
		);

		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[1] );

		snaklistview.stopEditing();

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			!snaklistview.isInitialValue(),
			'Snaklistview\'s value changed.'
		);

		assert.ok(
			snakLists[1].equals( snaklistview.value() ),
			'Verified new value.'
		);

		// Set an empty snak list and stop edit mode.
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, new wb.datamodel.SnakList() );

		snaklistview.stopEditing();

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Snaklistview\'s value is the initial value.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		$node.remove();
	} );

	QUnit.test( 'Dis- and enabling', function( assert ) {
		assert.expect( 5 );
		var $node = createSnaklistview( snakLists[0] ),
			snaklistview = $node.data( 'snaklistview' );

		/**
		 * Returns a string representing the state a snaklistview's snakviews are in.
		 *
		 * @param {jQuery.wikibase.snaklistview} snaklistview
		 * @return {string}
		 */
		function getSnakviewStates( snaklistview ) {
			var snakviews = snaklistview._listview.value(),
				isDisabled = true,
				isEnabled = true;

			for ( var i = 0; i < snakviews.length; i++ ) {
				isDisabled = isDisabled && snakviews[i].option( 'disabled' );
				isEnabled = isEnabled && !snakviews[i].option( 'disabled' );
			}

			if ( isDisabled && !isEnabled ) {
				return 'disabled';
			} else if ( !isDisabled && isEnabled ) {
				return 'enabled';
			} else {
				return 'mixed';
			}
		}

		assert.equal(
			getSnakviewStates( snaklistview ),
			'enabled',
			'snaklistview\'s snakviews are enabled.'
		);

		$node.on( 'snaklistviewdisable', function( e ) {
			assert.ok(
				true,
				'Triggered "disable" event.'
			);
			QUnit.start();
		} );

		$node.on( 'snaklistviewenable', function( e ) {
			assert.ok(
				true,
				'Triggered "enable" event.'
			);
			QUnit.start();
		} );

		QUnit.stop();

		snaklistview.disable();

		assert.equal(
			getSnakviewStates( snaklistview ),
			'disabled',
			'Disabled snaklistview\'s snakviews.'
		);

		QUnit.stop();

		snaklistview.enable();

		assert.equal(
			getSnakviewStates( snaklistview ),
			'enabled',
			'Eabled snaklistview\'s snakviews.'
		);
	} );

	QUnit.test( 'singleProperty option', function( assert ) {
		assert.expect( 4 );
		var $node = createSnaklistview( snakLists[0], { singleProperty: true } ),
			snaklistview = $node.data( 'snaklistview' );

		assert.ok(
			snaklistview._listview.items().length > 0,
			'Initialized snaklistview with more than one item.'
		);

		function testPropertyLabelVisibility( assert, snaklistview ) {
			$.each( snaklistview._listview.items(), function( i, snakviewNode ) {
				var $snakview = $( snakviewNode ),
					snakview = snaklistview._lia.liInstance( $snakview );

				if ( i === 0 ) {
					assert.ok(
						snakview._propertyLabelVisible,
						'Topmost snakview\'s property label is visible.'
					);
				} else {
					assert.ok(
						!snakview._propertyLabelVisible,
						'Property label of snakview that is not on top of the snaklistview is not '
							+ 'visible.'
					);
				}
			} );
		}

		testPropertyLabelVisibility( assert, snaklistview );
	} );

} )( jQuery, wikibase, dataValues, QUnit );
