( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.singlebuttontoolbar;

/**
 * "Remove" toolbar widget by default offering a "remove" button.
 * @class jQuery.wikibase.removetoolbar
 * @extends jQuery.wikibase.singlebuttontoolbar
 * @since 0.4
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [options]
 * @param {string} [options.label=mw.msg( 'wikibase-remove' )]
 * @param {string} [options.eventName='remove']
 * @param {string} [buttonCssClassSuffix='remove']
 * @param {boolean} [icon=false]
 *        Whether the toolbar button should be an icon instead of the text.
 */
$.widget( 'wikibase.removetoolbar', PARENT, {
	/**
	 * @see inheritdoc
	 */
	options: {
		label: mw.msg( 'wikibase-remove' ),
		eventName: 'remove',
		buttonCssClassSuffix: 'remove',
		icon: false
	},

	// TODO: Move code to base constructor and button widget.
	/**
	 * @inheritdoc
	 * @protected
	 */
	_initDefaultButton: function( $scrapedButton ) {
		var $button = PARENT.prototype._initDefaultButton.call( this, $scrapedButton );

		if ( !this.options.icon ) {
			return $button;
		}

		var $link = $button.children( 'a' );

		$link
		.attr( 'title', $link.text() )
		.text( '' )
		.append( $( '<span class="wb-icon"/>' ) );

		return $button;
	}
} );

}( mediaWiki, jQuery ) );
