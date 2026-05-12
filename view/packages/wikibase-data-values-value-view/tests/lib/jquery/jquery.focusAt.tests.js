/**
 * @license GNU GPL v2+
 * @author Daniel Werner
 */
( function () {
	'use strict';

	/**
	 * Returns a DOM object within a HTML page
	 *
	 * @return {jQuery}
	 *
	 * @throws {Error} If the test runs in a non-browser environment or on a unsuitable HTML page.
	 */
	function getDomInsertionTestViewport() {
		var body = $( 'body' );

		if ( !body.length ) {
			throw new Error( 'Can only run this test on a HTML page with "body" tag in the browser.' );
		}
		return body;
	}

	QUnit.module( 'jquery.focusAt' );

	QUnit.test( 'plugin initialization', function( assert ) {
		assert.strictEqual(
			typeof $.fn.focusAt,
			'function',
			'"jQuery.focusAt" is available'
		);
	} );

	var elemsCasesData = [
		{
			title: 'div',
			elem: $( '<div/>' ),
			focusable: false
		}, {
			title: 'input',
			elem: $( '<input/>', { text: 'foo 123' } ),
			focusable: true
		}, {
			title: 'textarea',
			elem: $( '<textarea/>', { text: 'bar 123' } ),
			focusable: true
		}, {
			title: 'span(+tabindex)',
			elem: $( '<span tabindex="0">foo</span>' ),
			focusable: true
		}, {
			title: 'span',
			elem: $( '<span/>' ),
			focusable: false
		}
	];

	elemsCasesData.forEach( function ( params ) {
		QUnit.test( 'Focusing with valid parameter', function( assert ) {
			var $dom = getDomInsertionTestViewport(),
				positions = [ 0, 1, 4, 9, 9999, 'start', 'end', -1, -3, -9999 ];

			$.each( positions, function( i, pos ) {
				// Put element in DOM, since Firefox expects this
				$dom.append( params.elem );
				assert.ok(
					params.elem.focusAt( pos ),
					'focusAt takes "' + pos + '" as a valid position for the element'
				);
				params.elem.remove();
			} );
		} );
	} );

	elemsCasesData.forEach( function ( params ) {
		QUnit.test( 'Focusing with invalid parameter', function( assert ) {
			var positions = [ null, undefined, 'foo', [], {} ];

			$.each( positions, function( i, pos ) {
				assert.throws(
					function() {
						params.elem.focusAt( pos );
					},
					'focusAt does not take "' + pos + '" as a valid position for the element'
				);
			} );
		} );
	} );

	elemsCasesData.forEach( function ( params ) {
		QUnit.test( 'Focusing element, not in DOM yet', function( assert ) {
			var $dom = getDomInsertionTestViewport(),
				elem = params.elem;

			if ( !$dom.length ) {
				throw new Error( 'Can only run this test on a HTML page with "body" tag in the browser.' );
			}

			try {
				assert.ok(
					elem.focusAt( 0 ),
					'Can call focusAt on element not in DOM yet.'
				);
			} catch ( e ) {
				assert.ok(
					e.name === 'NS_ERROR_FAILURE' && e.result === 0x80004005,
					'Unable to focus since browser requires element to be in the DOM.'
				);
				// eslint-disable-next-line qunit/no-early-return
				return;
			}

			$( ':focus' ).blur();
			elem.appendTo( $dom );

			assert.strictEqual(
				$( ':focus' ).length,
				0,
				'After inserting focused element into DOM, the element is not focused since there is' +
					'no state tracking focus for those elements not in the DOM.'
			);
			elem.remove();
		} );
	} );

	elemsCasesData.forEach( function ( params ) {
		QUnit.test( 'Focusing element in DOM', function( assert ) {
			var $dom = getDomInsertionTestViewport(),
				elem = params.elem,
				isOk;

			if ( !$dom.length ) {
				throw new Error( 'Can only run this test on a HTML page with "body" tag in the browser.' );
			}

			$( ':focus' ).blur();
			elem.appendTo( $dom );

			// Check if focussing actually works
			elem.focus();
			if ( !elem.is( ':focus' ) ) {
				assert.ok( 'Could not test because focussing does not work.' );
				// eslint-disable-next-line qunit/no-early-return
				return;
			}
			elem.blur();
			assert.strictEqual( elem.is( ':focus' ), false );

			assert.ok(
				elem.focusAt( 0 ),
				'Can call focusAt on element in DOM'
			);

			if ( !params.focusable ) {
				assert.strictEqual(
					$( ':focus' ).length,
					0,
					'Element is a non-focusable element and no focus is active'
				);
			} else {
				isOk = $( ':focus' ).filter( elem ).length;
				assert.ok( isOk, 'Focused element has focus set.' );
			}
			elem.remove();
		} );
	} );

}() );
