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
var createAliasesview = function( options ) {
	options = $.extend( {
		value: new wb.datamodel.MultiTerm( 'en', ['a', 'b', 'c'] )
	}, options || {} );

	var $aliasesview = $( '<div/>' )
		.addClass( 'test_aliasesview' )
		.appendTo( 'body' )
		.aliasesview( options );

	$aliasesview.data( 'aliasesview' )._save = function() {
		return $.Deferred().resolve().promise();
	};

	return $aliasesview;
};

QUnit.module( 'jquery.wikibase.aliasesview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_aliasesview' ).each( function() {
			var $aliasesview = $( this ),
				aliasesview = $aliasesview.data( 'aliasesview' );

			if ( aliasesview ) {
				aliasesview.destroy();
			}

			$aliasesview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	assert.throws(
		function() {
			createAliasesview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $aliasesview = createAliasesview(),
		aliasesview = $aliasesview.data( 'aliasesview' );

	assert.ok(
		aliasesview instanceof $.wikibase.aliasesview,
		'Created widget'
	);

	aliasesview.destroy();

	assert.ok(
		$aliasesview.data( 'aliasesview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'Instantiating tagadata widget on startEditing()', function( assert ) {
	assert.expect( 1 );
	var $aliasesview = createAliasesview(),
		aliasesview = $aliasesview.data( 'aliasesview' );

	QUnit.stop();

	aliasesview.startEditing()
	.done( function() {
		assert.ok(
			aliasesview.$list.data( 'tagadata' ) !== undefined,
			'Instantiated tagadata widget.'
		);
	} )
	.fail( function() {
		assert.ok(
			false,
			'Failed to start edit mode.'
		);
	} )
	.always( function() {
		QUnit.start();
	} );
} );

QUnit.test( 'startEditing() & stopEditing()', 6, function( assert ) {
	var $aliasesview = createAliasesview(),
		aliasesview = $aliasesview.data( 'aliasesview' );

	$aliasesview
	.on( 'aliasesviewafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'aliasesviewafterstopediting', function( event, dropValue ) {
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

		$aliasesview
		.one( 'aliasesviewafterstartediting.aliasesviewtest', function( event ) {
			$aliasesview.off( '.aliasesviewtest' );
			deferred.resolve();
		} )
		.one( 'aliasesviewafterstopediting.aliasesviewtest', function( event, dropValue ) {
			$aliasesview.off( '.aliasesviewtest' );
			deferred.resolve();
		} );

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
		aliasesview.startEditing();
	} );

	addToQueue( $queue, function() {
		aliasesview.startEditing();
	}, false );

	addToQueue( $queue, function() {
		aliasesview.stopEditing( true );
	} );

	addToQueue( $queue, function() {
		aliasesview.stopEditing( true );
	}, false );

	addToQueue( $queue, function() {
		aliasesview.stopEditing();
	}, false );

	addToQueue( $queue, function() {
		aliasesview.startEditing();
	} );

	addToQueue( $queue, function() {
		aliasesview.$list.data( 'tagadata' ).getTags().first().find( 'input' ).val( 'b' );
		aliasesview.stopEditing();
	} );

	addToQueue( $queue, function() {
		aliasesview.startEditing();
	} );

	addToQueue( $queue, function() {
		aliasesview.$list.data( 'tagadata' ).getTags().first()
			.removeClass( 'tagadata-choice-equal' ).find( 'input' ).val( 'd' );
		aliasesview.stopEditing();
	} );

	$queue.dequeue( 'tests' );
} );

QUnit.test( 'setError()', function( assert ) {
	assert.expect( 1 );
	var $aliasesview = createAliasesview(),
		aliasesview = $aliasesview.data( 'aliasesview' );

	$aliasesview
	.on( 'aliasesviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	aliasesview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 3 );
	var $aliasesview = createAliasesview(),
		aliasesview = $aliasesview.data( 'aliasesview' ),
		newValue = null;

	assert.throws(
		function() {
			aliasesview.value( newValue );
		},
		'Trying to set no value fails.'
	);

	newValue = new wb.datamodel.MultiTerm( 'de', ['x', 'y'] );
	aliasesview.value( newValue );

	assert.ok(
		aliasesview.value().equals( newValue ),
		'Set new value.'
	);

	newValue = new wb.datamodel.MultiTerm( 'en', [] );
	aliasesview.value( newValue );

	assert.ok(
		aliasesview.value().equals( newValue ),
		'Set another value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
