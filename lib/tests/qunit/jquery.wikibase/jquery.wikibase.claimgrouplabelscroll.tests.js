/**
 * QUnit tests for "wikibase.claimgrouplabelscroll" jQuery widget.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, QUnit, ClaimGroupLabelScrollWidget ) {
	'use strict';

	/**
	 * Returns a DOM object within a HTML page suitable for testing the widget on.
	 * @return {jQuery}
	 * @throws {Error} If the test runs in a non-browser environment or on a unsuitable HTML page.
	 */
	function newTestNode() {
		var $body = $( 'body' );

		if( !$body.length ) {
			throw new Error( 'Can only run this test on a HTML page with "body" tag in the browser.' );
		}

		return $( '<div>' ).appendTo( $body );
	}

	QUnit.module( 'jquery.wikibase.claimgrouplabelscroll', {
		teardown: function() {
			$.each( ClaimGroupLabelScrollWidget.activeInstances(), function( i, instance ) {
				instance.destroy();
				instance.element.remove();
			} );
		}
	} );

	QUnit.test( 'widget definition', function( assert ) {
		assert.ok(
			$.isFunction( ClaimGroupLabelScrollWidget ),
			'"jQuery.wikibase.claimgrouplabelscroll" (widget definition) is defined'
		);

		assert.ok(
			$.isFunction( $.fn.claimgrouplabelscroll ),
			'"jQuery.fn.claimgrouplabelscroll" (widget bridge) is defined'
		);

		assert.strictEqual(
			ClaimGroupLabelScrollWidget.activeInstances().length,
			0,
			'Zero active instance of the widget before first instantiation'
		);
	} );

	QUnit.test( 'widget instantiation and destruction', function( assert ) {
		var $testNode = newTestNode().claimgrouplabelscroll(),
			instance = $testNode.data( 'claimgrouplabelscroll' );

		assert.ok(
			instance instanceof ClaimGroupLabelScrollWidget,
			'Widget successfully instantiated'
		);

		assert.strictEqual(
			ClaimGroupLabelScrollWidget.activeInstances()[0],
			instance,
			'Instantiated widget returned by $.wikibase.claimgrouplabelscroll.activeInstances()'
		);

		instance.destroy();

		assert.strictEqual(
			ClaimGroupLabelScrollWidget.activeInstances().length,
			0,
			'Zero active instances of the widget after destruction of only active instance'
		);

	} );

}( jQuery, QUnit, jQuery.wikibase.claimgrouplabelscroll ) );
