/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function () {
	'use strict';
	/* jshint newcap: false */

	QUnit.module( 'jquery.animateWithEvent' );

	QUnit.test( 'special start callback execution before options.start', function( assert ) {
		var done = assert.async( 2 );
		var optionsStartCallbackDone = 0;
		var specialStartCallbackDone = 0;

		$( '<div/>' ).animateWithEvent(
			'fooeventpurpose',
			'fadeOut',
			{
				start: function( animation ) {
					optionsStartCallbackDone++;
					done();
				}
			}, function( animationEvent ) {
				assert.strictEqual(
					optionsStartCallbackDone, 0,
					'last argument start callback got fired before options.start callback.'
				);
				specialStartCallbackDone++;
			}
		);

		assert.strictEqual(
			optionsStartCallbackDone,
			1,
			'options.start callback got fired.'
		);
		assert.strictEqual(
			specialStartCallbackDone,
			1,
			'Last argument start callback got fired.'
		);

		done();
	} );

	QUnit.test( 'special start callback', function( assert ) {
		var $elem = $( '<div/>' );

		return $elem.animateWithEvent(
			'foopurpose',
			{ width: 200 },
			{},
			function( animationEvent ) {
				assert.strictEqual(
					this, $elem.get( 0 ),
					'Context of the callback is the DOM node to be animated.'
				);
				assert.ok(
					animationEvent.animationOptions,
					'First argument is an instance of jQuery.AnimationEvent.'
				);

			}
		);
	} );

	QUnit.test( 'options.start callback', function( assert ) {
		var $elem = $( '<div/>' );
		var animationEventsAnimation;

		return $elem.animateWithEvent(
			'foopurpose',
			{ width: 200 },
			{
				start: function( animation ) {
					assert.strictEqual(
						this, $elem.get( 0 ),
						'Context of the callback is the DOM node to be animated.'
					);
					assert.strictEqual(
						animation, animationEventsAnimation,
						'First argument ist the animation object which is set to the '
							+ 'AnimationEvent instance\'s "animation" field in the callback '
							+ 'given as animateWithEvent\'s last argument.'
					);

				}
			}, function( animationEvent ) {
				animationEventsAnimation = animationEvent.animation;
			}
		);
	} );

	QUnit.test( 'On jQuery set of multiple elements', function( assert ) {
		var done = assert.async( 3 );
		var $elems = $( '<div/>' ).add( $( '<span/> ' ) ).add( $( '<div/> ' ) );
		var $confirmedElems = $();
		var animationEventInstances = [];

		$elems.animateWithEvent( 'fadesomethingin', 'fadeIn', function( animationEvent ) {
			var elem = animationEvent.animation.elem;
			$confirmedElems = $confirmedElems.add( elem );

			if ( $.inArray( animationEvent, animationEventInstances ) < 0 ) {
				animationEventInstances.push( animationEvent );
			}

			if ( $confirmedElems.length >= $elems.length ) {
				done();
			}
		} ).promise().done( done );

		assert.ok(
			$elems.length === $confirmedElems.length
				&& $elems.not( $confirmedElems ).length === 0,
			'Initial callback got called for all ' + $elems.length + ' elements of the jQuery set.'
		);

		assert.strictEqual(
			animationEventInstances.length,
			$elems.length,
			'Each callback got its own instance of jQuery.AnimationEvent.'
		);

		done();
	} );

	QUnit.test( 'Error cases', function( assert ) {
		assert.throws(
			function() {
				$( '<div/>' ).animateWithEvent(
					'fooeventpurpose',
					'fooAnimateFunction'
				);
			},
			'Can not use unknown animation function in arguments.'
		);

		assert.throws(
			function() {
				$( '<div/>' ).animateWithEvent();
			},
			'Throws error if called without parameters. At least event purpose has to be given.'
		);
	} );

	QUnit.test( 'Two arguments are sufficient', function( assert ) {
		var $node = $( '<div/>' );
		var done = assert.async( 2 );

		$node.animateWithEvent(
			'fooeventpurpose',
			{ width: 200 }
		).promise().done( function() {
			assert.ok(
				true,
				'Can call with only first two arguments'
			);
			done();
		} );

		$node = $( '<div/>' );

		$node.animateWithEvent(
			'xxxevent'
		).promise().done( function() {
			assert.ok(
				true,
				'Can call with only first argument'
			);
			done();
		} );
	} );

}() );
