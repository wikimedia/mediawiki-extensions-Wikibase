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
 */
$.widget( 'wikibase.removetoolbar', PARENT, {
	/**
	 * @see inheritdoc
	 */
	options: {
		label: mw.msg( 'wikibase-remove' ),
		eventName: 'remove',
		buttonCssClassSuffix: 'remove',
	}

} );

}( mediaWiki, jQuery ) );
