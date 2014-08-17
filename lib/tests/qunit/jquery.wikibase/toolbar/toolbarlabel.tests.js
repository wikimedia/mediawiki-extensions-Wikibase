/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a new $.wikibase.label instance.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestLabel = function( options ) {
		options = options || {};

		return $( '<span/>' )
			.text( 'Text' )
			.addClass( 'test_label' )
			.toolbarlabel( options );
	};

	QUnit.module( 'jquery.wikibase.toolbarlabel', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_label' ).each( function( i, node ) {
				var $node = $( node );

				if( $node.data( 'toolbarlabel' ) ) {
					$node.data( 'toolbarlabel' ).destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Init and destroy.', function( assert ) {
		var $node = newTestLabel(),
			label = $node.data( 'toolbarlabel' );

		assert.ok(
			$node.data( 'toolbarlabel' ) instanceof $.wikibase.toolbarlabel,
			'Initialized label.'
		);

		assert.equal(
			$node.text(),
			'Text',
			'Verified node text.'
		);

		label.destroy();

		assert.equal(
			$node.data( 'toolbarlabel' ),
			undefined,
			'Destroyed label.'
		);
	} );

	QUnit.test( 'Disable and enable', function( assert ) {
		var $node = newTestLabel(),
			label = $node.data( 'toolbarlabel' );

		assert.ok(
			!label.option( 'disabled' ),
			'Label is enabled.'
		);

		label.disable();

		assert.ok(
			label.option( 'disabled' ),
			'Disabled label.'
		);

		label.disable();

		assert.ok(
			label.option( 'disabled' ),
			'Label still disabled after disabling twice.'
		);

		label.enable();

		assert.ok(
			!label.option( 'disabled' ),
			'Enabled label.'
		);

		label.enable();

		assert.ok(
			!label.option( 'disabled' ),
			'Label still enabled after enabling twice.'
		);
	} );

}( jQuery, QUnit ) );
