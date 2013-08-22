/**
 * @since 0.4
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb, dv ) {
	'use strict';

	var snakLists = [
		new wb.SnakList( [
			new wb.PropertyValueSnak( 'p1', new dv.StringValue( 'a' ) ),
			new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'b' ) ),
			new wb.PropertyValueSnak( 'p3', new dv.StringValue( 'c' ) )
		] ),
		new wb.SnakList( [
			new wb.PropertyValueSnak( 'p4', new dv.StringValue( 'd' ) )
		] )
	];

	/**
	 * Generates a snaklistview widget suitable for testing.
	 *
	 * @param {wikibase.SnakList} [value]
	 * @return {jQuery}
	 */
	function createSnaklistview( value ) {
		return $( '<div/>' )
			.addClass( 'test_snaklistview' )
			.snaklistview( { value: ( value || null ) } );
	}

	/**
	 * Sets a snak list on a given snaklistview retaining the initial snak list (since it gets
	 * overwritten by using value() to set a snak list).
	 *
	 * @param {jquery.wikibase.snaklistview} snaklistview
	 * @param {wikibase.SnakList} value
	 * @return {jquery.wikibase.snaklistview}
	 */
	function setValueKeepingInitial( snaklistview, value ) {
		var initialValue = snaklistview._snakList;

		snaklistview.value( value );
		snaklistview._snakList = initialValue;

		return snaklistview;
	}

	QUnit.module( 'jquery.wikibase.snaklistview', window.QUnit.newWbEnvironment( {
		teardown: function() {
			$( '.test_snaklistview' ).each( function( i, node ) {
				var $node = $( node ),
					snaklistview = $node.data( 'snaklistview' );

				if( snaklistview ) {
					snaklistview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.ok(
			snaklistview !== undefined,
			'Initialized snaklistview widget.'
		);

		assert.strictEqual(
			snaklistview.value(),
			null,
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

		snaklistview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);
	} );

	QUnit.test( 'Setting and getting value while not in edit mode', function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		assert.strictEqual(
			snaklistview.value(),
			null,
			'Snaklistview is empty.'
		);

		assert.ok(
			snaklistview.value( snakLists[0] ).equals( snakLists[0] ),
			'Set snak list.'
		);

		assert.ok(
			snaklistview.value().equals( snakLists[0] ),
			'Verified set snak list.'
		);

		assert.ok(
			!snaklistview.isInEditMode(),
			'Snaklistview is not in edit mode.'
		);

		assert.ok(
			snaklistview.value( snakLists[1] ).equals( snakLists[1] ),
			'Overwrote snak list.'
		);

		assert.ok(
			snaklistview.value().equals( snakLists[1] ),
			'Verified set snak list.'
		);

		assert.ok(
			snaklistview.value( new wb.SnakList() ),
			'Set empty snak list.'
		);

		assert.strictEqual(
			snaklistview.value(),
			null,
			'Verified snaklistview being empty.'
		);
	} );

	QUnit.test( 'Setting value while in edit mode', function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		snaklistview.startEditing();

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		assert.strictEqual(
			snaklistview.value(),
			null,
			'Snaklistview is empty.'
		);

		assert.ok(
			snaklistview.value( snakLists[0] ).equals( snakLists[0] ),
			'Set snak list.'
		);

		assert.ok(
			snaklistview.value().equals( snakLists[0] ),
			'Verified set snak list.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		assert.ok(
			snaklistview.value( snakLists[1] ).equals( snakLists[1] ),
			'Overwrote snak list.'
		);

		assert.ok(
			snaklistview.value().equals( snakLists[1] ),
			'Verified set snak list.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);

		assert.ok(
			snaklistview.value( new wb.SnakList() ),
			'Set empty snak list.'
		);

		assert.strictEqual(
			snaklistview.value(),
			null,
			'Verified snaklistview being empty.'
		);

		assert.ok(
			snaklistview.isInEditMode(),
			'Snaklistview is in edit mode.'
		);
	} );

	QUnit.test( 'isInitialValue()', function( assert ) {
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

		snaklistview = setValueKeepingInitial( snaklistview, new wb.SnakList() );

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

		snaklistview = setValueKeepingInitial( snaklistview, new wb.SnakList() );

		assert.ok(
			!snaklistview.isInitialValue(),
			'snaklistview does not have the initial value after setting value to an empty snak list.'
		);

		snaklistview = setValueKeepingInitial(
			snaklistview, wb.SnakList.newFromJSON( snakLists[0].toJSON() )
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'snaklistview has initial value again after setting to a copy of the initial snak list.'
		);
	} );

	QUnit.test( 'Basic start and stop editing', 7, function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop( 2 );

		$node.on( 'snaklistviewstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "startediting" event.'
			);

			QUnit.start();
		} );

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
			snaklistview.value(),
			null,
			'Snaklistview is still empty.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();
	} );

	QUnit.test( 'Basic start and stop editing of filled snaklistview', 7, function( assert ) {
		var $node = createSnaklistview( snakLists[0] ),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop( 2 );

		$node.on( 'snaklistviewstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "startediting" event.'
			);

			QUnit.start();
		} );

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
			snaklistview.isInitialValue(),
			true,
			'Snaklistview is still empty.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();
	} );

	QUnit.test( 'enterNewItem()', function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

		QUnit.stop( 3 );

		$node.on( 'snaklistviewstartediting', function( e ) {
			assert.ok(
				true,
				'Triggered "startediting" event.'
			);
			QUnit.start();
		} );

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
			!snaklistview.isInitialValue(),
			'Snaklistview does not feature its initial value anymore.'
		);

		assert.ok(
			!snaklistview.isValid(),
			'Snaklistview is not valid due to pending value.'
		);
	} );

	QUnit.test( 'cancelEditing()', function( assert ) {
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
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

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
		snaklistview = setValueKeepingInitial( snaklistview, new wb.SnakList() );

		snaklistview.stopEditing( true );

		assert.ok(
			!snaklistview.isInEditMode(),
			'Left edit mode.'
		);

		assert.ok(
			snaklistview.isInitialValue(),
			'Verified reset to initial value.'
		);
	} );

	QUnit.test( 'Stopping edit mode retaining value', function( assert ) {
		var $node = createSnaklistview(),
			snaklistview = $node.data( 'snaklistview' );

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
		snaklistview = setValueKeepingInitial( snaklistview, new wb.SnakList() );

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
			snaklistview.value(),
			null,
			'Snaklistview is empty.'
		);
	} );

	QUnit.test( 'Dis- and enabling', function( assert ) {
		var $node = createSnaklistview( snakLists[0] ),
			snaklistview = $node.data( 'snaklistview' );

		/**
		 * Returns a string representing the state a snaklistview's snakviews are in.
		 *
		 * @param {jquery.wikibase.snaklistview} snaklistview
		 * @return {string}
		 */
		function getSnakviewStates( snaklistview ) {
			var snakviews = snaklistview._listview.value(),
				isDisabled = true,
				isEnabled = true;

			for( var i = 0; i < snakviews.length; i++ ) {
				isDisabled = isDisabled && snakviews[i].isDisabled();
				isEnabled = isEnabled && !snakviews[i].isDisabled();
			}

			if( isDisabled && !isEnabled ) {
				return 'disabled';
			} else if( !isDisabled && isEnabled ) {
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

} )( jQuery, mediaWiki, wikibase, dataValues );
