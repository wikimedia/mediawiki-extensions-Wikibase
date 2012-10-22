/**
 * HTML template store initilisation
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( $, mw, undefined ) {
	'use strict';

	$( document ).ready( function() {
		mw.templates = new mw.Map();
		mw.templates.set( mw.config.get( 'wgTemplateStore' ) );
		mw.template = function( key, params ) {
			var template = new mw.Message( mw.templates, key, params );
			return template.plain();
		};

	} );

}( jQuery, mediaWiki ) );
