/**
 * @since 0.4
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a new $.wikibase.label instance.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestLabel = function( options ) {
		options = $.extend( {
			content: 'Text'
		}, options );

		return $( '<span/>' )
			.addClass( 'test_label' )
			.wblabel( options );
	};

	QUnit.module( 'jquery.wikibase.wblabel', QUnit.newWbEnvironment( {
		teardown: function() {
			$( '.test_label' ).each( function( i, node ) {
				var $node = $( node );

				if( $node.data( 'wblabel' ) ) {
					$node.data( 'wblabel' ).destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Set and get content.', function( assert ) {
		var $node = newTestLabel(),
			label = $node.data( 'wblabel' );

		assert.ok(
			label.getContent() === 'Text',
			'Initialized label.'
		);

		label.setContent( 'Foo' );

		assert.equal(
			label.getContent(),
			'Foo',
			'Set new text content.'
		);

		var jQueryObj = $( '<span/>' );
		label.setContent( jQueryObj );

		assert.equal(
			label.getContent()[0],
			jQueryObj[0], // compare with containing node
			'Set jQuery object as content.'
		);

		label.destroy();

		assert.equal(
			$node.data( 'wblabel' ),
			undefined,
			'Destroyed label.'
		);
	} );

	QUnit.test( 'Apply and remove focus.', function( assert ) {
		var $node = newTestLabel(),
			label = $node.data( 'wblabel' );

		// attach label to body to be able to receive focus
		$node.appendTo( 'body' );

		assert.ok(
			!$node.is( ':focus' ),
			'Label is not focused.'
		);

		label.setFocus();

		assert.ok(
			$node.is( ':focus' ),
			'Label is focused. (An error at this stage might also occur if you removed the ' +
				'focus from the browser window.)'
		);

		assert.equal(
			$node.prop( 'tabIndex' ),
			0,
			'Label has tab index.'
		);

		$node.blur();

		assert.ok(
			!$node.is( ':focus' ),
			'Blurred label.'
		);

		assert.ok(
			!$node.prop( 'tabIndex' ),
			'Removed tab index from label after blurring.'
		);
	} );

	QUnit.test( 'Disable and enable', function( assert ) {
		var $node = newTestLabel(),
			label = $node.data( 'wblabel' );

		assert.ok(
			!label.isDisabled(),
			'Label is enabled.'
		);

		label.disable();

		assert.ok(
			label.isDisabled(),
			'Disabled label.'
		);

		label.disable();

		assert.ok(
			label.isDisabled(),
			'Label still disabled after disabling twice.'
		);

		label.enable();

		assert.ok(
			!label.isDisabled(),
			'Enabled label.'
		);

		label.enable();

		assert.ok(
			!label.isDisabled(),
			'Label still enabled after enabling twice.'
		);

		label.option( 'stateChangeable', false );

		assert.ok(
			!label.isDisabled(),
			'Unable to disable label after settings sateChangeable to false.'
		);

		label.option( 'stateChangeable', true );

		label.disable();

		label.option( 'stateChangeable', false );

		label.enable();

		assert.ok(
			label.isDisabled(),
			'Unable to enable disabled label when sateChangeable is set to false.'
		);

	} );

}( wikibase, jQuery, QUnit ) );
