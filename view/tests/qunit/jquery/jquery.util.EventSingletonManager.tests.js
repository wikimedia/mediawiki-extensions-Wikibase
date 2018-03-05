/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.util.EventSingletonManager' );

	QUnit.test( 'register() & unregister() (single source)', function ( assert ) {
		assert.expect( 2 );
		var manager = new $.util.EventSingletonManager(),
			$source = $( '<div/>' ),
			$target = $( '<div/>' ),
			event = $.Event( 'custom' );

		manager.register(
			$source.get( 0 ),
			$target.get( 0 ),
			'custom.namespace',
			function ( event, source ) {
				assert.ok(
					true,
					'Triggered event "' + event.type + '.'
				);

				assert.equal(
					$source.get( 0 ),
					source,
					'Verified source element being passed to the handler.'
				);
			}
		);

		$target.trigger( event );

		manager.unregister( $source.get( 0 ), $target.get( 0 ), 'custom.namespace' );

		$target.trigger( event );
	} );

	QUnit.test( 'register() & unregister() (multiple sources)', function ( assert ) {
		assert.expect( 4 );
		var manager = new $.util.EventSingletonManager(),
			$sources = $( '<div/><div/>' ),
			sources = $sources.map( function () { return this; } ),
			$target = $( '<div/>' ),
			triggeredForSources = [],
			event = $.Event( 'custom' ),
			handler = function ( event, source ) {
				triggeredForSources.push( source );
			};

		manager.register( $sources.get( 0 ), $target.get( 0 ), 'custom.namespace', handler );
		manager.register( $sources.get( 1 ), $target.get( 0 ), 'custom.namespace', handler );

		$target.trigger( event );

		assert.equal(
			triggeredForSources.length,
			$sources.length,
			'Handler has been called for every source.'
		);

		assert.ok(
			$.inArray( sources[ 0 ], triggeredForSources ) !== -1,
			'Handler was called for first source.'
		);

		assert.ok(
			$.inArray( sources[ 1 ], triggeredForSources ) !== -1,
			'Handler was called for second source.'
		);

		manager.unregister( $sources.get( 1 ), $target.get( 0 ), 'custom.namespace' );

		$target.trigger( event );

		assert.equal(
			triggeredForSources[ 2 ],
			$sources.get( 0 ),
			'Handler was called once again after unregistering a source.'
		);

		manager.unregister( $sources.get( 0 ), $target.get( 0 ), '.namespace' );
	} );

	QUnit.test( 'unregister() & unregister() (multiple events)', function ( assert ) {
		assert.expect( 8 );
		var manager = new $.util.EventSingletonManager(),
			$source = $( '<div/>' ),
			$target = $( '<div/>' ),
			event1 = $.Event( 'custom1' ),
			event2 = $.Event( 'custom2' ),
			event3 = $.Event( 'custom3' ),
			event4 = $.Event( 'custom4' );

		manager.register(
			$source.get( 0 ),
			$target.get( 0 ),
			'custom1.namespace custom2.namespace custom3.namespace custom4.othernamespace',
			function ( event, source ) {
				assert.ok(
					true,
					'Triggered event "' + event.type + '".'
				);
			}
		);

		$target.trigger( event1 );
		$target.trigger( event2 );
		$target.trigger( event3 );
		$target.trigger( event4 );

		manager.unregister(
			$source.get( 0 ),
			$target.get( 0 ),
			'custom1.namespace'
		);

		$target.trigger( event1 ); // no action
		$target.trigger( event2 );
		$target.trigger( event3 );
		$target.trigger( event4 );

		manager.unregister( $source.get( 0 ), $target.get( 0 ), '.namespace' );

		$target.trigger( event1 ); // no action
		$target.trigger( event2 ); // no action
		$target.trigger( event3 ); // no action
		$target.trigger( event4 );

		manager.unregister( $source.get( 0 ), $target.get( 0 ), '.othernamespace' );

		$target.trigger( event1 ); // no action
		$target.trigger( event2 ); // no action
		$target.trigger( event3 ); // no action
		$target.trigger( event4 ); // no action
	} );

}( jQuery, QUnit ) );
