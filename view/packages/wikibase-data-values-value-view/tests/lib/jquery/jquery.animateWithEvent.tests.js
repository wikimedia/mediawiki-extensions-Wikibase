/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, QUnit ) {
	'use strict';
	/* jshint newcap: false */

	QUnit.module( 'jquery.animateWithEvent' );

	QUnit.test( 'special start callback execution before options.start', function( assert ) {
		var optionsStartCallbackDone = 0;
		var specialStartCallbackDone = 0;

		QUnit.stop();
		$( '<div/>' ).animateWithEvent(
			'fooeventpurpose',
			'fadeOut',
			{
				start: function( animation ) {
					optionsStartCallbackDone++;
					QUnit.start();
				}
			}, function( animationEvent ) {
				assert.ok(
					!optionsStartCallbackDone,
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
	} );

	QUnit.test( 'special start callback', function( assert ) {
		var $elem = $( '<div/>' );

		QUnit.stop();

		$elem.animateWithEvent(
			'foopurpose',
			{ width: 200 },
			{},
			function( animationEvent ) {
				assert.ok(
					this === $elem.get( 0 ),
					'Context of the callback is the DOM node to be animated.'
				);
				assert.ok(
					animationEvent instanceof $.AnimationEvent,
					'First argument is an instance of jQuery.AnimationEvent.'
				);

			}
		).promise().done( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'options.start callback', 2, function( assert ) {
		var $elem = $( '<div/>' );
		var animationEventsAnimation;

		QUnit.stop();

		$elem.animateWithEvent(
			'foopurpose',
			{ width: 200 },
			{
				start: function( animation ) {
					assert.ok(
						this === $elem.get( 0 ),
						'Context of the callback is the DOM node to be animated.'
					);
					assert.ok(
						animation === animationEventsAnimation,
						'First argument ist the animation object which is set to the '
							+ 'AnimationEvent instance\'s "animation" field in the callback '
							+ 'given as animateWithEvent\'s last argument.'
					);

				}
			}, function( animationEvent ) {
				animationEventsAnimation = animationEvent.animation;
			}
		).promise().done( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'On jQuery set of multiple elements', function( assert ) {
		var $elems = $( '<div/>' ).add( $( '<span/> ' ) ).add( $( '<div/> ' ) );
		var $confirmedElems = $();
		var animationEventInstances = [];

		QUnit.stop( 2 );

		$elems.animateWithEvent( 'fadesomethingin', 'fadeIn', function( animationEvent ) {
			var elem = animationEvent.animation.elem;
			$confirmedElems = $confirmedElems.add( elem );

			if( $.inArray( animationEvent, animationEventInstances ) < 0 ) {
				animationEventInstances.push( animationEvent );
			}

			if( $confirmedElems.length >= $elems.length ) {
				QUnit.start();
			}
		} ).promise().done( function() {
			QUnit.start();
		} );

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

	QUnit.test( 'Two arguments are sufficient', 2, function( assert ) {
		var $node = $( '<div/>' );

		QUnit.stop();

		$node.animateWithEvent(
			'fooeventpurpose',
			{ width: 200 }
		).promise().done( function() {
			QUnit.start();

			assert.ok(
				true,
				'Can call with only first two arguments'
			);
		} );

		$node = $( '<div/>' );

		QUnit.stop();

		$node.animateWithEvent(
			'xxxevent'
		).promise().done( function() {
			QUnit.start();

			assert.ok(
				true,
				'Can call with only first argument'
			);
		} );
	} );

}( jQuery, QUnit ) );
