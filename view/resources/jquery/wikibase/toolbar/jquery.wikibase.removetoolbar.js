( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.singlebuttontoolbar;

/**
 * "Remove" toolbar widget by default offering a "remove" button.
 * @class jQuery.wikibase.removetoolbar
 * @extends jQuery.wikibase.singlebuttontoolbar
 * @since 0.4
 * @licence GNU GPL v2+
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

		$button.addClass( [
			'oo-ui-widget',
			'oo-ui-widget-enabled',
			'oo-ui-buttonElement',
			'oo-ui-buttonElement-frameless',
			'oo-ui-iconElement',
			'oo-ui-labelElement',
			'oo-ui-buttonWidget'
		].join( ' ' ) );

		var $link = $button.children( 'a' );

		$link
		.attr( 'title', $link.text() )
		.text( '' )
		.addClass( 'oo-ui-buttonElement-button' )
		.append( $( '<span/>' ).addClass( 'oo-ui-iconElement-icon oo-ui-icon-remove' ) );

		return $button;
	}
} );

}( mediaWiki, jQuery ) );
