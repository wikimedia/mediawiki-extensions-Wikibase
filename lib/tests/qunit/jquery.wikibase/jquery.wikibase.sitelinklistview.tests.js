/**
 * @licence GNU GPL v2+
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
		entityId: 'i am an entity id',
		api: 'i am an api',
		entityStore: new wb.store.EntityStore(),
		allowedSiteIds: ['aawiki', 'enwiki']
	}, options );

	var $sitelinklistview = $( '<table/>' )
		.addClass( 'test_sitelinklistview')
		.appendTo( $( 'body' ) )
		.sitelinklistview( options );

	var sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	sitelinklistview._saveSiteLink = function( siteLink ) {
		if( !( siteLink instanceof wb.datamodel.SiteLink ) ) {
			throw new Error( 'SiteLink object expected' );
		} else {
			return ( new $.Deferred() ).resolve().promise();
		}
	};

	return $sitelinklistview;
}

QUnit.module( 'jquery.wikibase.sitelinklistview', QUnit.newWbEnvironment( {
	config: {
		'wbSiteDetails': {
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

			if( sitelinklistview ) {
				sitelinklistview.destroy();
			}

			$sitelinklistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create and destroy', function( assert ) {
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
		$sitelinklistview.data( 'sitelinkview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'isFull()', function( assert ) {
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
	var $sitelinklistview = createSitelinklistview( {
			value: [new wb.datamodel.SiteLink( 'enwiki', 'enwiki-page' )]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview.isValid(),
		'Verified isValid() returning TRUE.'
	);

	var listview = sitelinklistview.$listview.data( 'listview' ),
		lia = listview.listItemAdapter();

	lia.liInstance( listview.items().first() ).isValid = function() {
		return false;
	};

	assert.ok(
		!sitelinklistview.isValid(),
		'Verified isValid() returning FALSE.'
	);
} );

QUnit.test( 'isInitialValue()', function( assert ) {
	var $sitelinklistview = createSitelinklistview( {
			value: [new wb.datamodel.SiteLink( 'enwiki', 'enwiki-page' )]
		} ),
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

	assert.ok(
		sitelinklistview.isInitialValue(),
		'Verified isInitialValue() returning TRUE.'
	);

	var listview = sitelinklistview.$listview.data( 'listview' ),
		$sitelinkview = listview.addItem( new wb.datamodel.SiteLink( 'aawiki', 'aawiki-page' ) );

	assert.ok(
		!sitelinklistview.isInitialValue(),
		'FALSE after adding another value.'
	);

	listview.removeItem( $sitelinkview );

	assert.ok(
		sitelinklistview.isInitialValue(),
		'TRUE after resetting to initial value.'
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

	function p1() {
		$sitelinklistview.one( 'sitelinklistviewafterstartediting', p2 );
		sitelinklistview.startEditing();
		sitelinklistview.startEditing(); // should not trigger event
	}

	function p2() {
		$sitelinklistview.one( 'sitelinklistviewafterstopediting', p3 );
		sitelinklistview.stopEditing( true );
		sitelinklistview.stopEditing( true ); // should not trigger event
		sitelinklistview.stopEditing(); // should not trigger event
	}

	function p3() {
		$sitelinklistview.one( 'sitelinklistviewafterstartediting', p4 );
		sitelinklistview.startEditing();

		// Mock adding a new item:
		var listview = sitelinklistview.$listview.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$sitelinkview = listview.addItem( new wb.datamodel.SiteLink( 'aawiki', 'aawiki-page' ) );
		lia.liInstance( $sitelinkview ).startEditing();
	}

	function p4() {
		$sitelinklistview.one( 'sitelinklistviewafterstopediting', p5 );
		sitelinklistview.stopEditing( true ); // Have to drop item added above
	}

	function p5() {
		QUnit.start();
	}

	p1();
	QUnit.stop();
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

	$sitelinklistview
	.on( 'listviewenternewitem', function( event, $sitelinkview ) {
		assert.ok(
			true,
			'Added listview item.'
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

}( jQuery, wikibase, QUnit ) );
