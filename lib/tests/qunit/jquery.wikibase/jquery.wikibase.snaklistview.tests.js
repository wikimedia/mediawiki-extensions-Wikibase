/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $, wb, dv, vf, vv, QUnit ) {
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

	var snakSet = [
		new wb.datamodel.PropertyValueSnak( 'p1',  new dv.StringValue( 'a' ) ),
		new wb.datamodel.PropertyValueSnak( 'p1',  new dv.StringValue( 'b' ) ),
		new wb.datamodel.PropertyValueSnak( 'p2',  new dv.StringValue( 'c' ) ),
		new wb.datamodel.PropertyValueSnak( 'p2',  new dv.StringValue( 'd' ) ),
		new wb.datamodel.PropertyValueSnak( 'p2',  new dv.StringValue( 'e' ) ),
		new wb.datamodel.PropertyValueSnak( 'p3',  new dv.StringValue( 'f' ) ),
		new wb.datamodel.PropertyValueSnak( 'p4',  new dv.StringValue( 'g' ) )
	];

	// We need a filled entity store for the instances of $.wikibase.snakview.variations.Value
	// and $.wikibase.snakview created by $.wikibase.snaklistview.
	var entities = {
		p1: new wb.store.FetchedContent( {
			title: new mw.Title( 'Property:P1' ),
			content: new wb.datamodel.Property( {
				id: 'P1',
				type: 'property',
				datatype: 'string'
			} )
		} ),
		p2: new wb.store.FetchedContent( {
			title: new mw.Title( 'Property:P2' ),
			content: new wb.datamodel.Property( {
				id: 'P2',
				type: 'property',
				datatype: 'string'
			} )
		} ),
		p3: new wb.store.FetchedContent( {
			title: new mw.Title( 'Property:P3' ),
			content: new wb.datamodel.Property( {
				id: 'P3',
				type: 'property',
				datatype: 'string'
			} )
		} ),
		p4: new wb.store.FetchedContent( {
			title: new mw.Title( 'Property:P4' ),
			content: new wb.datamodel.Property( {
				id: 'P4',
				type: 'property',
				datatype: 'string'
			} )
		} )
	};

	var entityStore = {
		get: function( entityId ) {
			return $.Deferred().resolve( entities[entityId] );
		}
	};

	var valueViewBuilder = new wb.ValueViewBuilder(
		new vv.ExpertStore(),
		new vf.ValueFormatterStore( vf.NullFormatter )
	);

	/**
	 * Generates a snaklistview widget suitable for testing.
	 *
	 * @param {wb.datamodel.SnakList} [value]
	 * @param {Object} [additionalOptions]
	 * @return {jQuery}
	 */
	function createSnaklistview( value, additionalOptions ) {
		var options = $.extend( additionalOptions, {
			value: ( value || null ),
			entityStore: entityStore,
			valueViewBuilder: valueViewBuilder
		} );

		return $( '<div>' )
			.addClass( 'test_snaklistview' )
			.snaklistview( options );
	}

	/**
	 * Sets a snak list on a given snaklistview retaining the initial snak list (since it gets
	 * overwritten by using value() to set a snak list).
	 *
	 * @param {$.wikibase.snaklistview} snaklistview
	 * @param {wb.datamodel.SnakList} value
	 * @return {$.wikibase.snaklistview}
	 */
	function setValueKeepingInitial( snaklistview, value ) {
		var initialValue = snaklistview._snakList;

		snaklistview.value( value );
		snaklistview._snakList = initialValue;

		return snaklistview;
	}

	/**
	 * Returns the concatenated string values of a snak list's snaks.
	 *
	 * @param {wb.datamodel.SnakList} snakList
	 * @return {string}
	 */
	function snakOrder( snakList ) {
		var snakValues = [];

		snakList.each( function( i, snak ) {
			snakValues.push( snak.getValue().getValue() );
		} );

		return snakValues.join( '' );
	}

	QUnit.module( 'jquery.wikibase.snaklistview', window.QUnit.newMwEnvironment( {
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
			snaklistview.value( new wb.datamodel.SnakList() ),
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
			snaklistview.value( new wb.datamodel.SnakList() ),
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
			snaklistview, wb.datamodel.SnakList.newFromJSON( snakLists[0].toJSON() )
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
			'Snaklistview is still empty.'
		);

		// Should not trigger any events since not in edit mode:
		snaklistview.stopEditing();

		$node.remove();
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
			snaklistview.value(),
			null,
			'Snaklistview is empty.'
		);

		$node.remove();
	} );

	QUnit.test( 'Dis- and enabling', function( assert ) {
		var $node = createSnaklistview( snakLists[0] ),
			snaklistview = $node.data( 'snaklistview' );

		/**
		 * Returns a string representing the state a snaklistview's snakviews are in.
		 *
		 * @param {$.wikibase.snaklistview} snaklistview
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

	QUnit.test( 'move()', function( assert ) {
		var snakList = new wb.datamodel.SnakList( snakSet );

		/**
		 * Array of test case definitions. Test case definition structure:
		 * [0] => Index of element to move
		 * [1] => Index where to move element
		 * [2] => Expected result when concatenating the string values of the snak list's snaks.
		 * @type {*[][]}
		 */
		var testCases = [
			[ 0, 1, 'bacdefg' ],
			[ 0, 5, 'cdeabfg' ],
			[ 0, 6, 'cdefabg' ],
			[ 0, 7, 'cdefgab' ],
			[ 1, 0, 'bacdefg' ],
			[ 1, 5, 'cdeabfg' ],
			[ 1, 6, 'cdefabg' ],
			[ 1, 7, 'cdefgab' ],
			[ 2, 0, 'cdeabfg' ],
			[ 2, 3, 'abdcefg' ],
			[ 2, 4, 'abdecfg' ],
			[ 2, 6, 'abfcdeg' ],
			[ 2, 7, 'abfgcde' ],
			[ 3, 0, 'cdeabfg' ],
			[ 3, 2, 'abdcefg' ],
			[ 3, 4, 'abcedfg' ],
			[ 3, 6, 'abfcdeg' ],
			[ 3, 7, 'abfgcde' ],
			[ 4, 0, 'cdeabfg' ],
			[ 4, 2, 'abecdfg' ],
			[ 4, 3, 'abcedfg' ],
			[ 4, 6, 'abfcdeg' ],
			[ 4, 7, 'abfgcde' ],
			[ 5, 0, 'fabcdeg' ],
			[ 5, 2, 'abfcdeg' ],
			[ 5, 7, 'abcdegf' ],
			[ 6, 0, 'gabcdef' ],
			[ 6, 2, 'abgcdef' ],
			[ 6, 5, 'abcdegf' ]
		];

		var $node,
			snaklistview;

		for( var i = 0; i < testCases.length; i++ ) {
			$node = createSnaklistview( snakList );
			snaklistview = $node.data( 'snaklistview' );

			snaklistview.move( snakSet[testCases[i][0]], testCases[i][1] );

			assert.equal(
				snakOrder( snaklistview.value() ),
				testCases[i][2],
				'Verified moving a snak with test set #' + i + '.'
			);
		}

		$node = createSnaklistview( snakList );
		snaklistview = $node.data( 'snaklistview' );
		snaklistview.move( snakSet[1], 1 );

		assert.equal(
			snakOrder( snaklistview.value() ),
			'abcdefg',
			'Nothing changed when trying to move a snak to an index it already has.'
		);

		assert.throws(
			function() {
				$node = createSnaklistview( snakList );
				snaklistview = $node.data( 'snaklistview' );
				snaklistview.move( snakSet[0], 4 );
			},
			'move() throws an error when trying to move a snak to an invalid index.'
		);
	} );

	QUnit.test( 'moveUp() and moveDown()', function( assert ) {
		var snakList = new wb.datamodel.SnakList( snakSet ),
			$node,
			snaklistview;

		/**
		 * Array of test case definitions for moveUp() and moveDown() methods. Test case definition
		 * structure:
		 * [0] => Resulting order after moving the element having the same index in the snak list up.
		 * [1] => Resulting order after moving the element having the same index in the snak list down.
		 * @type {string[][]}
		 */
		var testCases = [
			['abcdefg', 'bacdefg' ],
			['bacdefg', 'cdeabfg' ],
			['cdeabfg', 'abdcefg' ],
			['abdcefg', 'abcedfg' ],
			['abcedfg', 'abfcdeg' ],
			['abfcdeg', 'abcdegf' ],
			['abcdegf', 'abcdefg' ]
		];

		for( var i = 0; i < testCases.length; i++ ) {
			$node = createSnaklistview( snakList );
			snaklistview = $node.data( 'snaklistview' );

			snaklistview.moveUp( snakSet[i] );

			assert.equal(
				snakOrder( snaklistview.value() ),
				testCases[i][0],
				'Moving up a snak with test set #' + i + '.'
			);

			$node = createSnaklistview( snakList );
			snaklistview = $node.data( 'snaklistview' );

			snaklistview.moveDown( snakSet[i] );

			assert.equal(
				snakOrder( snaklistview.value() ),
				testCases[i][1],
				'Moved down a snak with test set #' + i + '.'
			);
		}
	} );

	QUnit.test( 'singleProperty option', function( assert ) {
		var $node = createSnaklistview( snakLists[0], { singleProperty: true } ),
			snaklistview = $node.data( 'snaklistview' );

		// Append node to body in order to correctly detect visibility:
		$node.appendTo( 'body' );

		assert.ok(
			snaklistview._listview.items().length > 0,
			'Initialized snaklistview with more than one item.'
		);

		function testPropertyLabelVisibility( assert, snaklistview ) {
			$.each( snaklistview._listview.items(), function( i, snakviewNode ) {
				var $snakview = $( snakviewNode ),
					snakview = snaklistview._lia.liInstance( $snakview );

				if( i === 0 ) {
					assert.ok(
						snakview.propertyLabelIsVisible(),
						'Topmost snakview\'s property label is visible.'
					);
				} else {
					assert.ok(
						!snakview.propertyLabelIsVisible(),
						'Property label of snakview that is not on top of the snaklistview is not '
							+ 'visible.'
					);
				}
			} );
		}

		// Initial test:
		testPropertyLabelVisibility( assert, snaklistview );

		// Move a snakview without affecting topmost snakview:
		snaklistview.moveDown( snakLists[1].toArray()[0] );
		testPropertyLabelVisibility( assert, snaklistview );

		// Move topmost snakview:
		snaklistview.moveDown( snakLists[0].toArray()[0] );
		testPropertyLabelVisibility( assert, snaklistview );
	} );

} )( mediaWiki, jQuery, wikibase, dataValues, valueFormatters, jQuery.valueview, QUnit );
