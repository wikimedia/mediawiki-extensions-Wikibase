/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, dv ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' ),
		snakLists = [
			new datamodel.SnakList( [
				new datamodel.PropertyValueSnak( 'p1', new dv.StringValue( 'a' ) ),
				new datamodel.PropertyValueSnak( 'p2', new dv.StringValue( 'b' ) ),
				new datamodel.PropertyValueSnak( 'p3', new dv.StringValue( 'c' ) )
			] ),
			new datamodel.SnakList( [
				new datamodel.PropertyValueSnak( 'p4', new dv.StringValue( 'd' ) )
			] )
		];

	var listItemAdapter = wb.tests.getMockListItemAdapter( 'snakview', function () {
		var _value = this.options.value;
		this.options.locked = this.options.locked || {};
		this.snak = function ( value ) {
			if ( arguments.length ) {
				_value = value;
			}
			return _value;
		};
		this.startEditing = function () {
			this._trigger( 'change' );
			this._trigger( 'afterstartediting' );
		};
		this.stopEditing = function () {};

		this.showPropertyLabel = function () {
			this._propertyLabelVisible = true;
		};
		this.hidePropertyLabel = function () {
			this._propertyLabelVisible = false;
		};
	} );

	/**
	 * Generates a snaklistview widget suitable for testing.
	 *
	 * @param {datamodel.SnakList} [value]
	 * @param {Object} [additionalOptions]
	 * @return {jQuery}
	 */
	function createSnaklistview( value, additionalOptions ) {
		var options = $.extend( additionalOptions, {
			value: value || undefined,
			getListItemAdapter: function () {
				return listItemAdapter;
			}
		} );

		return $( '<div>' )
			.addClass( 'test_snaklistview' )
			.snaklistview( options );
	}

	/**
	 * Sets a snak list on a given snaklistview retaining the initial snak list (since it gets
	 * overwritten by using value() to set a snak list).
	 *
	 * @param {jQuery.wikibase.snaklistview} snaklistview
	 * @param {datamodel.SnakList} value
	 * @return {jQuery.wikibase.snaklistview}
	 */
	function setValueKeepingInitial( snaklistview, value ) {
		var initialValue = snaklistview.option( 'value' );

		snaklistview.value( value );
		snaklistview.options.value = initialValue;

		return snaklistview;
	}

	QUnit.module( 'jquery.wikibase.snaklistview', window.QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_snaklistview' ).each( function ( i, node ) {
				var $node = $( node ),
					snaklistview = $node.data( 'snaklistview' );

				if ( snaklistview ) {
					snaklistview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.notStrictEqual(
			snaklistview,
			undefined,
			'Initialized snaklistview widget.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview contains no snaks.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Snaklistview is not in edit mode.'
		);

		assert.notStrictEqual(
			snaklistview.$listview.data( 'listview' ),
			undefined,
			'Initialized listview.'
		);

		snaklistview.destroy();

		assert.strictEqual(
			snaklistview.$listview,
			null,
			'Destroyed listview.'
		);

		assert.throws(
			function () {
				createSnaklistview( {
					value: null
				} );
			},
			'Throwing error when trying to instantiate widget without a proper value.'
		);
	} );

	QUnit.test( 'Setting and getting value while not in edit mode', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		snaklistview.value( snakLists[ 0 ] );

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Set snak list.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Snaklistview is not in edit mode.'
		);

		snaklistview.value( snakLists[ 1 ] );

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 1 ] ),
			true,
			'Overwrote snak list.'
		);

		snaklistview.value( new datamodel.SnakList() );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Set empty snak list.'
		);
	} );

	QUnit.test( 'Setting value while in edit mode', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		snaklistview.startEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Snaklistview is in edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		snaklistview.value( snakLists[ 0 ] );

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Set snak list.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Snaklistview is in edit mode.'
		);

		snaklistview.value( snakLists[ 1 ] );

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 1 ] ),
			true,
			'Overwrote snak list.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Snaklistview is in edit mode.'
		);

		snaklistview.value( new datamodel.SnakList() );

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Set empty snak list.'
		);

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Snaklistview is in edit mode.'
		);
	} );

	QUnit.test( 'Basic start and stop editing', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		var done = assert.async( 3 );

		$node.on( 'snaklistviewafterstartediting', function ( e ) {
			assert.true(
				true,
				'Triggered "afterstartediting" event.'
			);

			done();
		} );

		snaklistview.startEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Entered edit mode.'
		);

		// Should not trigger any events since in edit mode already:
		snaklistview.startEditing();

		$node.on( 'snaklistviewafterstopediting', function ( e ) {
			assert.true(
				true,
				'Triggered "afterstopediting" event.'
			);

			done();
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
		done();
	} );

	QUnit.test( 'Basic start and stop editing of filled snaklistview', function ( assert ) {
		var $node = createSnaklistview( snakLists[ 0 ] ),
			snaklistview = $node.data( 'snaklistview' );

		var done = assert.async( 3 );

		$node.on( 'snaklistviewafterstartediting', function ( e ) {
			assert.true(
				true,
				'Triggered "afterstartediting" event.'
			);

			done();
		} );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( document.body );

		snaklistview.startEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Entered edit mode.'
		);

		// Should not trigger any events since in edit mode already:
		snaklistview.startEditing();

		$node.on( 'snaklistviewafterstopediting', function ( e ) {
			assert.true(
				true,
				'Triggered "afterstopediting" event.'
			);

			done();
		} );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Snaklistview still features initial value.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();

		$node.remove();
		done();
	} );

	QUnit.test( 'enterNewItem()', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		var done = assert.async( 3 );

		$node.on( 'snaklistviewafterstartediting', function ( e ) {
			assert.true(
				true,
				'Triggered "afterstartediting" event.'
			);
			done();
		} );

		$node.on( 'snaklistviewchange', function ( e ) {
			assert.true(
				true,
				'Triggered "change" event.'
			);
			done();
		} );

		snaklistview.enterNewItem();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			true,
			'Verified snaklistview being in edit mode.'
		);

		assert.strictEqual(
			snaklistview.value(),
			null,
			'Snaklistview is not valid due to pending value.'
		);

		done();
	} );

	QUnit.test( 'Stopping edit mode dropping value', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( document.body );

		// Start with empty snaklistview, set a snak list and stop edit mode:
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[ 0 ] );

		snaklistview.stopEditing( true );

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Verified reset to initial value.'
		);

		// Start with a filled snaklistview, set a snak list and stop edit mode:
		snaklistview.value( snakLists[ 0 ] );

		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[ 1 ] );

		snaklistview.stopEditing( true );

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Verified reset to initial value.'
		);

		// Set an empty snak list and stop edit mode.
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, new datamodel.SnakList() );

		snaklistview.stopEditing( true );

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Verified reset to initial value.'
		);

		$node.remove();
	} );

	QUnit.test( 'Stopping edit mode retaining value', function ( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		// We need to make sure the snaklistview is visible before startEditing,
		// because Firefox does not allow setting focus on a hidden element.
		$node.appendTo( document.body );

		// Start with empty snaklistview, set a snak list and stop edit mode:
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[ 0 ] );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().equals( snakLists[ 0 ] ),
			true,
			'Snaklistview\'s value changed.'
		);

		assert.strictEqual(
			snakLists[ 0 ].equals( snaklistview.value() ),
			true,
			'Verified new value.'
		);

		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, snakLists[ 1 ] );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snakLists[ 1 ].equals( snaklistview.value() ),
			true,
			'Verified new value.'
		);

		// Set an empty snak list and stop edit mode.
		snaklistview.startEditing();
		snaklistview = setValueKeepingInitial( snaklistview, new datamodel.SnakList() );

		snaklistview.stopEditing();

		assert.strictEqual(
			snaklistview.isInEditMode(),
			false,
			'Left edit mode.'
		);

		assert.strictEqual(
			snaklistview.value().length,
			0,
			'Snaklistview is empty.'
		);

		$node.remove();
	} );

	QUnit.test( 'Dis- and enabling', function ( assert ) {
		var done = assert.async( 3 );
		var $node = createSnaklistview( snakLists[ 0 ] ),
			snaklistview = $node.data( 'snaklistview' );

		/**
		 * Returns a string representing the state a snaklistview's snakviews are in.
		 *
		 * @return {string}
		 */
		function getSnakviewStates() {
			var snakviews = snaklistview._listview.value(),
				isDisabled = true,
				isEnabled = true;

			for ( var i = 0; i < snakviews.length; i++ ) {
				isDisabled = isDisabled && snakviews[ i ].option( 'disabled' );
				isEnabled = isEnabled && !snakviews[ i ].option( 'disabled' );
			}

			if ( isDisabled && !isEnabled ) {
				return 'disabled';
			} else if ( !isDisabled && isEnabled ) {
				return 'enabled';
			} else {
				return 'mixed';
			}
		}

		assert.strictEqual(
			getSnakviewStates(),
			'enabled',
			'snaklistview\'s snakviews are enabled.'
		);

		$node.on( 'snaklistviewdisable', function ( e ) {
			assert.true(
				true,
				'Triggered "disable" event.'
			);
			done();
		} );

		$node.on( 'snaklistviewenable', function ( e ) {
			assert.true(
				true,
				'Triggered "enable" event.'
			);
			done();
		} );

		snaklistview.disable();

		assert.strictEqual(
			getSnakviewStates(),
			'disabled',
			'Disabled snaklistview\'s snakviews.'
		);

		snaklistview.enable();

		assert.strictEqual(
			getSnakviewStates(),
			'enabled',
			'Eabled snaklistview\'s snakviews.'
		);

		done();
	} );

	QUnit.test( 'singleProperty option', function ( assert ) {
		var $node = createSnaklistview( snakLists[ 0 ], { singleProperty: true } ),
			snaklistview = $node.data( 'snaklistview' );

		assert.true(
			snaklistview._listview.items().length > 0,
			'Initialized snaklistview with more than one item.'
		);

		function testPropertyLabelVisibility() {
			// eslint-disable-next-line no-jquery/no-each-util
			$.each( snaklistview._listview.items(), function ( i, snakviewNode ) {
				var $snakview = $( snakviewNode ),
					snakview = snaklistview._lia.liInstance( $snakview );

				if ( i === 0 ) {
					assert.strictEqual(
						snakview._propertyLabelVisible,
						true,
						'Topmost snakview\'s property label is visible.'
					);
				} else {
					assert.strictEqual(
						snakview._propertyLabelVisible,
						false,
						'Property label of snakview that is not on top of the snaklistview is not '
							+ 'visible.'
					);
				}
			} );
		}

		testPropertyLabelVisibility();
	} );

}( wikibase, dataValues ) );
