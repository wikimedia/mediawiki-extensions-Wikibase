/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
( function () {
	'use strict';

	// Helper functions:
	var i = 0,
		iIncr = function() {
			i++;
		},
		iReset = function() {
			i = 0;
		};

	/**
	 * @param {Object} [attributes]
	 * @return {jQuery}
	 */
	function generateInputElement( attributes ) {
		return $( '<input/>', $.extend( {
			class: 'test_eachchange',
			type: 'text',
			name: 'test',
			value: ''
		}, attributes || {} ) );
	}

	QUnit.module( 'jquery.event.special.eachchange', {
		beforeEach: function() {
			iReset();
		},
		afterEach: function() {
			$( '.test_eachchange' ).remove();
		}
	} );

	QUnit.test(
		'Initialization',
		function( assert ) {
			var $input = $( '<input/>', { class: 'test_eachchange', type: 'text' } ),
				$inputNoType = $( '<input/>', { class: 'test_eachchange' } ),
				$textarea = $( '<textarea/>', { class: 'test_eachchange' } ),
				$div = $( '<div/>', { class: 'test_eachchange' } ),
				$object = $( {} );

			assert.strictEqual(
				$input.on( 'eachchange', iIncr ),
				$input,
				'Initialized event on a text input element.'
			);

			assert.strictEqual(
				$inputNoType.on( 'eachchange', iIncr ),
				$inputNoType,
				'Initialized event on an input element that has no "type" attribute.'
			);

			assert.strictEqual(
				$textarea.on( 'eachchange', iIncr ),
				$textarea,
				'Initialized event on a textarea.'
			);

			assert.strictEqual(
				$div.on( 'eachchange', iIncr ),
				$div,
				'Initialized event on a div element.'
			);

			assert.strictEqual(
				$object.on( 'eachchange', iIncr ),
				$object,
				'Initialized event on a plain object.'
			);
		}
	);

	QUnit.test( 'Triggering on a single input element', function( assert ) {
		var $subject = generateInputElement( { value: 'a' } );

		$subject.on( 'eachchange', iIncr );

		// Assign a second time:
		$subject.on( 'eachchange', function( event, previousValue ) {
			assert.strictEqual(
				previousValue,
				'a',
				'Received previous value.'
			);

			iIncr();
		} );

		$subject.trigger( 'eachchange' );

		assert.strictEqual(
			i,
			2,
			'Event is triggered only once per assignment.'
		);
	} );

	QUnit.test( 'Triggering with a namespace assigned', function( assert ) {
		var $subject = generateInputElement();

		$subject.on( 'eachchange.somenamespace', iIncr );
		$subject.on( 'eachchange.othernamespace', iIncr );

		$subject.trigger( 'eachchange.somenamespace' );

		assert.strictEqual(
			i,
			1,
			'Triggered "eachchange" handler with a specific namespace.'
		);

		$subject.trigger( 'eachchange' );

		assert.strictEqual(
			i,
			3,
			'Triggered "eachchange" handlers without a specific namespace.'
		);
	} );

	QUnit.test( 'Triggering with the event assigned twice with the same namespace',
		function( assert ) {
			var $subject = generateInputElement();

			$subject.on( 'eachchange.somenamespace', iIncr );
			$subject.on( 'eachchange.somenamespace', iIncr );

			$subject.trigger( 'eachchange.somenamespace' );

			assert.strictEqual(
				i,
				2,
				'Triggered multiple "eachchange" handlers featuring the same namespace.'
			);

			$subject.trigger( 'eachchange' );

			assert.strictEqual(
				i,
				4,
				'Triggered multiple "eachchange" handlers featuring the same namespace without '
					+ 'specifying the namespace.'
			);
		}
	);

	QUnit.test( 'Triggering using a native browser event', function( assert ) {
		var $subject = generateInputElement();

		$subject.on( 'eachchange', iIncr );

		// Issuing "input" and "keydown" to trigger "eachchange" in all browsers:
		$subject.trigger( $.Event( 'input' ) ).trigger( $.Event( 'keydown' ) );

		assert.ok(
			i > 0,
			'Triggered "eachchange" with a native browser event.'
		);
	} );

	QUnit.test( 'Triggering on a set of two input elements', function( assert ) {
		var $subject = generateInputElement().add( generateInputElement() );

		$subject.on( 'eachchange', iIncr );

		$subject.trigger( 'eachchange' );

		assert.strictEqual(
			i,
			2,
			'Triggered event on a set of two objects.'
		);
	} );

	QUnit.test( 'Bubbling up the DOM tree', function( assert ) {
		var $subject = generateInputElement(),
			$parent = $( '<div/>' );

		$parent
			.append( $subject )
			.on( 'eachchange', function( event, prevVal ) {
				assert.ok(
					true,
					'Event bubbled to parent DOM node.'
				);
			} );

		$subject.trigger( 'eachchange' );
	} );

	QUnit.test( 'Triggering event on an object that does not have a dedicated value',
		function( assert ) {
			var $subject = $( {} );

			$subject.on( 'eachchange', function( event, prevVal ) {
				assert.strictEqual(
					prevVal,
					null,
					'Event is triggered on object that does not have a dedicated value.'
				);
			} );

			$subject.trigger( 'eachchange' );
		}
	);

	QUnit.test( 'Setting prevVal', function( assert ) {
		var $subject = generateInputElement();
		var expectedPrevVal = 'a';

		$subject
		.appendTo( document.body )
		.val( 'a' )
		.on( 'eachchange', function( event, prevVal ) {
			assert.strictEqual(
				prevVal,
				expectedPrevVal,
				'prevVal is correct in first handler'
			);
		} )
		.on( 'eachchange', function( event, prevVal ) {
			assert.strictEqual(
				prevVal,
				expectedPrevVal,
				'prevVal is correct in second handler'
			);
		} );

		$subject.val( 'b' );
		$subject.trigger( 'input' );

		expectedPrevVal = 'b';
		$subject.val( 'c' );
		$subject.trigger( 'input' );

		$subject.remove();
	} );

}() );
