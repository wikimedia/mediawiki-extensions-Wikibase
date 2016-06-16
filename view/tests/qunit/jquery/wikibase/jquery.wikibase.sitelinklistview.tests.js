/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
	'use strict';

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
function createSitelinklistview( options ) {
	options = $.extend( {
		allowedSiteIds: ['aawiki', 'enwiki'],
		entityIdPlainFormatter: 'I am an EntityIdPlainFormatter'
	}, options );

	var $sitelinklistview = $( '<table/>' )
		.addClass( 'test_sitelinklistview' )
		.appendTo( $( 'body' ) )
		.sitelinklistview( options );

	var sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	sitelinklistview._saveSiteLink = function( siteLink ) {
		if ( !( siteLink instanceof wb.datamodel.SiteLink ) ) {
			throw new Error( 'SiteLink object expected' );
		} else {
			return ( new $.Deferred() ).resolve().promise();
		}
	};

	return $sitelinklistview;
}

QUnit.module( 'jquery.wikibase.sitelinklistview', QUnit.newWbEnvironment( {
	config: {
		wbSiteDetails: {
			aawiki: {
				apiUrl: 'http://aa.wikipedia.org/w/api.php',
				name: 'Qafár af',
				pageUrl: 'http://aa.wikipedia.org/wiki/$1',
				shortName: 'Qafár af',
				languageCode: 'aa',
				id: 'aawiki',
				group: 'wikipedia'
			},
			enwiki: {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English',
				languageCode: 'en',
				id: 'enwiki',
				group: 'wikipedia'
			},
			dewiki: {
				apiUrl: 'http://de.wikipedia.org/w/api.php',
				name: 'Deutsche Wikipedia',
				pageUrl: 'http://de.wikipedia.org/wiki/$1',
				shortName: 'Deutsch',
				languageCode: 'de',
				id: 'dewiki',
				group: 'wikipedia'
			}
		}
	},
	teardown: function() {
		$( '.test_sitelinklistview' ).each( function() {
			var $sitelinklistview = $( this ),
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

			if ( sitelinklistview ) {
				sitelinklistview.destroy();
			}

			$sitelinklistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create and destroy', function( assert ) {
	assert.expect( 2 );
	var $sitelinklistview = createSitelinklistview(),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview instanceof $.wikibase.sitelinklistview,
		'Created widget.'
	);

	sitelinklistview.destroy();

	assert.ok(
		$sitelinklistview.data( 'sitelinklistview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'Create and destroy with initial value', function( assert ) {
	assert.expect( 2 );
	var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' ),
		$sitelinklistview = createSitelinklistview( {
			value: [siteLink]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview instanceof $.wikibase.sitelinklistview,
		'Created widget.'
	);

	sitelinklistview.destroy();

	assert.ok(
		$sitelinklistview.data( 'sitelinklistview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'isFull()', function( assert ) {
	assert.expect( 2 );
	var $sitelinklistview = createSitelinklistview(),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		!sitelinklistview.isFull(),
		'Returning false.'
	);

	$sitelinklistview = createSitelinklistview( {
		value: [
			new wikibase.datamodel.SiteLink( 'aawiki', 'Main Page' ),
			new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' )
		]
	} );
	sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview.isFull(),
		'Retuning true.'
	);
} );

QUnit.test( 'isValid()', function( assert ) {
	assert.expect( 1 );
	var $sitelinklistview = createSitelinklistview( {
			value: [new wb.datamodel.SiteLink( 'enwiki', 'enwiki-page' )]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview.isValid(),
		'Verified isValid() returning TRUE.'
	);
} );

QUnit.test( 'isValid() with invalid sitelinkview', function( assert ) {
	assert.expect( 1 );
	var $sitelinklistview = createSitelinklistview( {
			value: []
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	sitelinklistview.enterNewItem();

	var listview = sitelinklistview.$listview.data( 'listview' ),
		lia = listview.listItemAdapter(),
		sitelinkview = lia.liInstance( listview.items().first() );

	sitelinkview.startEditing();
	sitelinkview.$siteId.find( 'input' ).val( 'foobar' );

	assert.ok(
		!sitelinklistview.isValid(),
		'Verified isValid() returning FALSE.'
	);
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	assert.expect( 5 );
	var $sitelinklistview = createSitelinklistview( {
			value: [new wb.datamodel.SiteLink( 'enwiki', 'enwiki-page' )]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
		listview = sitelinklistview.$listview.data( 'listview' );

	assert.ok(
		sitelinklistview.isInitialValue(),
		'Verified isInitialValue() returning TRUE.'
	);

	var $sitelinkview = listview.items().first(),
		lia = listview.listItemAdapter(),
		sitelink = lia.liInstance( $sitelinkview ).value();
	listview.removeItem( $sitelinkview );

	assert.notOk(
		sitelinklistview.isInitialValue(),
		'FALSE after removing an item.'
	);

	listview.addItem( sitelink );

	assert.ok(
		sitelinklistview.isInitialValue(),
		'TRUE after resetting to initial state.'
	);

	$sitelinkview = listview.addItem( new wb.datamodel.SiteLink( 'aawiki', 'aawiki-page' ) );

	assert.notOk(
		sitelinklistview.isInitialValue(),
		'FALSE after adding an item, even if the added item is unchanged.'
	);

	listview.removeItem( $sitelinkview );

	assert.ok(
		sitelinklistview.isInitialValue(),
		'TRUE after resetting to initial state.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 4, function( assert ) {
	var $sitelinklistview = createSitelinklistview( {
			value: [new wb.datamodel.SiteLink( 'enwiki', 'enwiki-page' )]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	$sitelinklistview
	.on( 'sitelinklistviewafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'sitelinklistviewafterstopediting', function( event, dropValue ) {
		assert.ok(
			true,
			'Stopped edit mode.'
		);
	} );

	/**
	 * @param {Function} func
	 * @param {boolean} expectingEvent
	 * @return {Object} jQuery.Promise
	 */
	function testEditModeChange( func, expectingEvent ) {
		var deferred = $.Deferred();

		if ( !expectingEvent ) {
			func();
			return deferred.resolve().promise();
		}

		$sitelinklistview
		.one( 'sitelinklistviewafterstartediting.sitelinklistviewtest', function( event ) {
			$sitelinklistview.off( '.sitelinklistviewtest' );
			deferred.resolve();
		} )
		.one(
			'sitelinklistviewafterstopediting.sitelinklistviewtest',
			function( event, dropValue ) {
				$sitelinklistview.off( '.sitelinklistviewtest' );
				deferred.resolve();
			}
		);

		func();

		return deferred.promise();
	}

	var $queue = $( {} );

	/**
	 * @param {jQuery} $queue
	 * @param {Function} func
	 * @param {boolean} [expectingEvent]
	 */
	function addToQueue( $queue, func, expectingEvent ) {
		if ( expectingEvent === undefined ) {
			expectingEvent = true;
		}
		$queue.queue( 'tests', function( next ) {
			QUnit.stop();
			testEditModeChange( func, expectingEvent ).always( function() {
				QUnit.start();
				next();
			} );
		} );
	}

	addToQueue( $queue, function() {
		sitelinklistview.startEditing();
	} );

	addToQueue( $queue, function() {
		sitelinklistview.startEditing();
	}, false );

	addToQueue( $queue, function() {
		sitelinklistview.stopEditing( true );
	} );

	addToQueue( $queue, function() {
		sitelinklistview.stopEditing( true );
	}, false );

	addToQueue( $queue, function() {
		sitelinklistview.stopEditing();
	}, false );

	addToQueue( $queue, function() {
		sitelinklistview.startEditing();
	} );

	addToQueue( $queue, function() {
		// Mock adding a new item:
		var listview = sitelinklistview.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$sitelinkview = listview.addItem( new wb.datamodel.SiteLink( 'aawiki', 'aawiki-page' ) );
		lia.liInstance( $sitelinkview ).startEditing();
		sitelinklistview.stopEditing( true );
	} );

	$queue.dequeue( 'tests' );
} );

QUnit.test( 'setError()', 1, function( assert ) {
	var $sitelinklistview = createSitelinklistview(),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	$sitelinklistview
	.addClass( 'wb-error' )
	.on( 'sitelinklistviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered toggleerror event.'
		);
	} );

	sitelinklistview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 2 );
	var value = [new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' )],
		$sitelinklistview = createSitelinklistview( {
			value: value
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.deepEqual(
		sitelinklistview.value(),
		value,
		'Retrieved initial value.'
	);

	value = [
		new wikibase.datamodel.SiteLink( 'aawiki', 'a' ),
		new wikibase.datamodel.SiteLink( 'aawiki', 'b' )
	];

	sitelinklistview.value( value );

	assert.deepEqual(
		sitelinklistview.value(),
		value,
		'Set and retrieved new value.'
	);
} );

QUnit.test( 'enterNewItem()', 2, function( assert ) {
	var $sitelinklistview = createSitelinklistview(),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	sitelinklistview.$listview
	.on( 'listviewenternewitem', function() {
		assert.ok(
			true,
			'Triggered listview\'s "enternewitem" event on the listview node.'
		);
	} );

	$sitelinklistview
	.on( 'listviewenternewitem', function( event, $sitelinkview ) {
		assert.ok(
			false,
			'Triggered listview\'s "enternewitem" event on the sitelinklistview node.'
		);
	} )
	.on( 'sitelinklistviewafterstartediting', function() {
		assert.ok(
			true,
			'Started sitelinklistview edit mode.'
		);
	} );

	sitelinklistview.enterNewItem();
} );

QUnit.test( 'remove empty sitelinkview when hitting backspace', function( assert ) {
	assert.expect( 2 );
	var $sitelinklistview = createSitelinklistview(),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	// Have to create two because the last empty item is never removed
	sitelinklistview.enterNewItem();
	sitelinklistview.enterNewItem();

	var listview = sitelinklistview.$listview.data( 'listview' ),
		$sitelinkview = listview.items().first();

	assert.equal( listview.items().length, 2 );
	var e = $.Event( 'keydown' );
	e.which = e.keyCode = $.ui.keyCode.BACKSPACE;
	$sitelinkview.trigger( e );

	assert.equal( listview.items().length, 1 );
} );

}( jQuery, wikibase, QUnit ) );
