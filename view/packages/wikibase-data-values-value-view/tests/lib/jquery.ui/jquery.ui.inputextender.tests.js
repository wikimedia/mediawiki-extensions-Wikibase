/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function () {
	'use strict';

	// TODO: Tests for hideWhenInputEmptyOption

	/**
	 * Factory for creating an input extender widget suitable for testing.
	 *
	 * @param {Object} [options] input extender options. If not given, the "content" option will be
	 *        set to some span node with text.
	 * @param {jQuery} [$input] Subject node for the widget.
	 */
	var newTestInputextender = function( options, $input ) {
		if ( $input === undefined && options instanceof $ ) {
			$input = options;
			options = undefined;
		}

		options = options || {
				content: [ $( '<span/>' ).addClass( 'defaultContent' ).text( 'default content' ) ]
			};

		$input = $input || $( '<input/>' ).appendTo( $( 'body' ) );
		$input
		.addClass( 'test_inputextender' )
		.inputextender( options );

		return $input.data( 'inputextender' );
	};

	/**
	 * Convenience function for testing behavior before/after/during showing and hiding extension.
	 *
	 * @example
	 * showAndHideExtensionAgain( assert, newTestInputextender(), {
	 * afterCallingShowExtension: function( instance ) {},
	 * whenFullyShown: function() { instance },
	 * afterCallingHideExtension: function( instance ) {},
	 * whenFullyHiddenAgain: function( instance ) {}
	 * } );
	 * @param assert
	 * @param {jQuery.ui.inputextender} instance
	 * @param {Object} [hideControl] jQuery.Promise. If given, then the "hide" action will only be
	 * done after the promise got resolved. If the promise gets rejected, then the hide
	 * action will never be performed.
	 * @param {Object} callbacks
	 * @return {Object} jQuery.Promise Resolved after final hiding is done. Can be rejected in case
	 * a hideControl has been injected and gets rejected.
	 */
	function showAndHideExtensionAgain( assert, instance, hideControl, callbacks ) {
		var deferred = $.Deferred();
		var done = assert.async( 2 );
		if ( !hideControl.done ) {
			callbacks = hideControl;
			// We will do the hideExtension() immediately in this case:
			hideControl = $.Deferred().resolve().promise();
		}

		instance.showExtension( function() {
			( callbacks.whenFullyShown || $.noop )( instance );

			hideControl.done( function() {
				instance.hideExtension( function() {
					( callbacks.whenFullyHiddenAgain || $.noop )( instance );

					deferred.resolve();
					done(); // *2*
				} );
				( callbacks.afterCallingHideExtension || $.noop )( instance );
			} )
			.fail( function() {
				deferred.reject();
			} )
			.always( done );
		} );
		( callbacks.afterCallingShowExtension || $.noop )( instance );

		return deferred.promise();
	}

	QUnit.module( 'jquery.ui.inputextender', {
		afterEach: function() {
			$( '.test_inputextender' ).each( function( i, node ) {
				var inputextender = $( node ).data( 'inputextender' );
				if ( inputextender ) {
					inputextender.destroy();
				}
				$( node ).remove();
			} );
		}
	} );

	QUnit.test( 'Initialization', function( assert ) {
		var extender = newTestInputextender();

		assert.ok(
			extender instanceof $.ui.inputextender,
			'Initialized widget.'
		);

		assert.strictEqual(
			extender.extensionIsActive(), false,
			'Extension not active initially.'
		);
	} );

	QUnit.test( 'Initialization on focused input', function( assert ) {
		var $input = $( '<input/>' ).appendTo( $( 'body' ) ).focus();
		if ( !$input.is( ':focus' ) ) {
			assert.ok( true, 'Could not test since focussing does not work.' );
			// eslint-disable-next-line qunit/no-early-return
			return;
		}
		var extender = newTestInputextender( $input );
		var isOk = extender.extensionIsActive();

		assert.ok( isOk, 'Extension active initially because input has focus.' );
	} );

	QUnit.test( 'Destruction', function( assert ) {
		var extender = newTestInputextender(),
			widgetBaseClass = extender.widgetBaseClass;

		extender.showExtension(); // Make sure extension is being constructed.
		extender.destroy();

		assert.strictEqual(
			$( '.test_inputextender' ).data( 'inputextender' ), undefined,
			'Destroyed widget.'
		);

		assert.strictEqual(
			$( '.' + widgetBaseClass + '-extension' ).length,
			0,
			'Removed extension node from DOM.'
		);
	} );

	QUnit.test( 'showExtension and extensionIsVisible/extensionIsActive', function( assert ) {
		showAndHideExtensionAgain( assert, newTestInputextender(), {
			afterCallingShowExtension: function( instance ) {
				assert.ok(
					instance.extensionIsActive(),
					'Extension is considered "active" immediately after calling "showExtension".'
				);

				assert.ok(
					instance.extensionIsVisible(),
					'Extension is visible immediately after calling "showExtension".'
				);

				assert.ok(
					instance.extension(),
					'extension() returns extension\'s DOM at this state.'
				);
			},
			whenFullyShown: function( instance ) {
				assert.ok(
					true,
					'showExtension( callback ) has triggered callback.'
				);
			},
			afterCallingHideExtension: function( instance ) {},
			whenFullyHiddenAgain: function( instance ) {}
		} );
	} );

	QUnit.test( 'hideExtension and extensionIsVisible/extensionIsActive', function( assert ) {
		showAndHideExtensionAgain( assert, newTestInputextender(), {
			afterCallingShowExtension: function( instance ) {},
			whenFullyShown: function( instance ) {},
			afterCallingHideExtension: function( instance ) {
				assert.strictEqual(
					instance.extensionIsActive(), false,
					'Extension is considered "inactive" immediately after calling "hideExtension".'
				);

				assert.strictEqual(
					instance.extensionIsVisible(), false,
					'Extension is regarded invisible immediately when calling "hideExtension".'
				);

				assert.strictEqual(
					instance.extension(),
					null,
					'extension() no longer returns extension\'s DOM at this stage.'
				);
			},
			whenFullyHiddenAgain: function( instance ) {
				assert.ok(
					true,
					'hideExtension( callback ) has triggered callback.'
				);

				assert.strictEqual(
					instance.extensionIsVisible(), false,
					'Extension is not visible anymore when callback gets called after "hide" is done.'
				);

				assert.strictEqual(
					instance.extension(),
					null,
					'extension() does not return extension\'s DOM in this state.'
				);
			}
		} );
	} );

	/**
	 * @param {QUnit.assert} assert
	 * @param {jQuery.ui.inputextender[]} inputExtenders
	 */
	function assertCurrentlyVisibleExtensions( assert, inputExtenders ) {
		var visibleExtenders = $.ui.inputextender.getInstancesWithVisibleExtensions();
		assert.ok(
			!$( inputExtenders ).not( $( visibleExtenders ) ).length
				&& !$( visibleExtenders ).not( $( inputExtenders ) ).length,
			'All inputextender instances expected to be visible are visible.'
		);
		assert.strictEqual(
			visibleExtenders.length,
			inputExtenders.length,
			inputExtenders.length + ' active extensions in total now.'
		);
	}

	/**
	 * Will take a list of inactive input extender instances and show their extensions one after
	 * another. After all of them are extended, the last one who got extended  will be hidden again,
	 * then, the others.
	 * After each step, getInstancesWithVisibleExtensions() will be tested for its return value.
	 *
	 * @param {QUnit.assert} assert
	 * @param {jQuery.ui.inputextender[]} inactiveExtenders
	 * @param {jQuery.ui.inputextender[]} activeExtenders private
	 * @return {Object} jQuery.Promise
	 */
	function testGetInstancesWithVisibleExtensions(
		assert, inactiveExtenders, activeExtenders
	) {
		activeExtenders = activeExtenders || [];

		// We will call this function recursively until all inactive extenders are active extenders.
		// After all extenders are active (no inactive extenders given in inactiveExtenders), the
		// promise returned by the function will be resolved. This will result in the hiding of the
		// extender which has been made active before, after that has been done, the one before that
		// one will be made inactive and so on.
		var hideControl = $.Deferred();

		if ( inactiveExtenders.length < 1 ) {
			return hideControl.resolve().promise();
		}

		var remainingInactiveExtenders = inactiveExtenders.slice();
		var testSubject = remainingInactiveExtenders.splice( 0, 1 )[0];

		return showAndHideExtensionAgain( assert, testSubject, hideControl.promise(), {
			afterCallingShowExtension: function( instance ) {
				var nowActiveExtenders = activeExtenders.slice();
				nowActiveExtenders.push( instance );

				assertCurrentlyVisibleExtensions( assert, nowActiveExtenders );

				testGetInstancesWithVisibleExtensions(
					assert, remainingInactiveExtenders, nowActiveExtenders
				).done( function() {
					hideControl.resolve();
				} );
			},
			whenFullyShown: function( instance ) {},
			afterCallingHideExtension: function( instance ) {},
			whenFullyHiddenAgain: function( instance ) {
				assertCurrentlyVisibleExtensions( assert, activeExtenders );
			}
		} ).done( function() {
			hideControl.resolve();
		} );
	}

	QUnit.test( '$.ui.inputextender.getInstancesWithVisibleExtensions', function( assert ) {
		var instances = $.ui.inputextender.getInstancesWithVisibleExtensions();

		assert.ok(
			Array.isArray( instances ) && instances.length === 0,
			'Returns empty array initially, before having any instances.'
		);

		// Build a few instances for the test:
		var extenders = [];
		while ( extenders.length < 5 ) {
			extenders.push( newTestInputextender() );
		}
		testGetInstancesWithVisibleExtensions( assert, extenders );
	} );

	QUnit.test( 'extension stays open, if focus moves inside it', function( assert ) {
		var $input = $( '<input/>' ).appendTo( $( 'body' ) ).focus();
		if ( !$input.is( ':focus' ) ) {
			assert.ok( true, 'Could not test since focussing does not work.' );
			// eslint-disable-next-line qunit/no-early-return
			return;
		}
		var done = assert.async();
		var spy = sinon.spy();
		var instance = newTestInputextender( {}, $input );

		$input.on( 'inputextenderaftertoggle', spy );

		// Add an input to the extension and move focus there (without clicking).
		$( '<input/>' ).appendTo( instance._$extension ).focus();

		setTimeout( function() {
			sinon.assert.notCalled( spy );
			assert.ok( instance.extensionIsVisible() );
			done();
		}, 300 );
	} );
}() );
