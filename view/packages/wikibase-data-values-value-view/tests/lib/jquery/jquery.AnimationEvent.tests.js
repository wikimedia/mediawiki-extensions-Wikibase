/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( $, QUnit, AnimationEvent, PurposedCallbacks ) {
	'use strict';
	/* jshint newcap: false */

	QUnit.module( 'jquery.AnimationEvent' );

	function assertSuccessfulConstruction( assert, instance, purpose ) {
		assert.ok(
			instance instanceof AnimationEvent,
			'Instantiated'
		);
		assert.ok(
			instance instanceof $.Event,
			'Instance of jQuery.Event.'
		);
		assert.strictEqual(
			instance.animationPurpose,
			purpose,
			'Animation purpose got copied into "animationPurpose" field.'
		);
		assert.ok(
			instance.animationCallbacks instanceof PurposedCallbacks.Facade,
			'"animationCallbacks" field is instance of jQuery.PurposedCallbacks.Facade'
		);
		assert.strictEqual(
			instance.type,
			'animation',
			'"type" field is set to "animation"'
		);
	}

	QUnit.test( 'construction without "new"', function( assert ) {
		assert.expect( 5 );
		assertSuccessfulConstruction( assert, AnimationEvent( 'nopurpose' ), 'nopurpose' );
	} );

	QUnit.test( 'construction with "new"', function( assert ) {
		assert.expect( 5 );
		assertSuccessfulConstruction( assert, new AnimationEvent( 'foo' ), 'foo' );
	} );

	QUnit.test( 'construction with custom fields given', function( assert ) {
		assert.expect( 7 );
		var fields = {
			someCustomField1: 'foo',
			someCustomField2: {}
		};
		var event = AnimationEvent( 'someanimation', fields );

		assertSuccessfulConstruction( assert, event, 'someanimation' );

		assert.ok(
			event.foo === fields.foo,
			'Custom field got copied.'
		);
		assert.ok(
			event.someCustomField2 === fields.someCustomField2,
			'Another custom field got copied, copy happens by reference, no deep extend.'
		);
	} );

	QUnit.test( 'animationOptions()', function( assert ) {
		assert.expect( AnimationEvent.ANIMATION_STEPS.length + 2 );
		var event = AnimationEvent( 'animationpurpose' );
		var predefined = {
			easing: 'swing',
			queue: true,
			duration: 200
		};

		var options = event.animationOptions( predefined );

		assert.ok(
			$.isPlainObject( options ),
			'Returns a plain object.'
		);
		assert.ok(
			options.easing === predefined.easing
			&& options.queue === predefined.queue
			&& options.duration === predefined.duration,
			'Returned object holds all values of the base object given to animationOptions().'
		);

		$.each( AnimationEvent.ANIMATION_STEPS, function( i, stepName ) {
			assert.ok(
				$.isFunction( options[ stepName ] ),
				'Returned options object\'s field "' + stepName + '" is a function.'
			);
		} );
	} );

	QUnit.test( 'ANIMATION_STEPS', function( assert ) {
		assert.expect( 2 );

		assert.ok(
			$.isArray( AnimationEvent.ANIMATION_STEPS ),
			'Is an array.'
		);
		// This might be kind of pointless, but simply make sure that no one changes this without
		// changing tests as well, being absolutely sure about it.
		var expectedSteps =
			[ 'start', 'step', 'progress', 'complete', 'done', 'fail', 'always' ];
		assert.ok(
			$( AnimationEvent.ANIMATION_STEPS ).not( expectedSteps ).length === 0
			&& $( expectedSteps ).not( AnimationEvent.ANIMATION_STEPS ).length === 0,
			'Contains expected steps.'
		);
	} );

	function testAnimationOptionsGeneratedCallbacks( assert, testStep ) {
		var event = AnimationEvent( 'animationpurpose' );
		var predefined = {};

		var firedPredefined, firedCallbacksMember;
		var resetFired = function() { firedPredefined = firedCallbacksMember = 0; };

		resetFired();

		predefined[ testStep ] = function() {
			firedPredefined++;
			assert.ok(
				!firedCallbacksMember,
				'Predefined "' + testStep + '" callback got executed first.'
			);
		};
		event.animationCallbacks.add( testStep, function() {
			firedCallbacksMember++;
		} );

		var options = event.animationOptions( predefined );
		options[ testStep ]();

		assert.strictEqual(
			firedPredefined,
			1,
			'Fired predefined callback.'
		);
		assert.strictEqual(
			firedCallbacksMember,
			1,
			'Fired callback registered to event\'s "animationCallbacks" field.'
		);

		resetFired();

		// Execute all other generated step callbacks as well, verify that they execute and that
		// they do not trigger the testStep's callback again.
		$.each( AnimationEvent.ANIMATION_STEPS, function( i, step ) {
			if ( step !== testStep ) {
				options[ step ]();
			}
		} );
		assert.ok(
			firedPredefined === 0 && firedCallbacksMember === 0,
			'Fired callbacks generated for all other option fields, they are independent of the "'
				+ testStep + '" one.'
		);
	}

	$.each( AnimationEvent.ANIMATION_STEPS, function( i, step ) {
		QUnit.test(
			'animationOptions(). ' + step + ' callbacks test',
			function( assert ) {
				assert.expect( 4 );
				testAnimationOptionsGeneratedCallbacks( assert, step );
			} );
	} );

}( jQuery, QUnit, jQuery.AnimationEvent, jQuery.PurposedCallbacks ) );
