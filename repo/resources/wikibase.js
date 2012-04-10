/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
window.wikibase = new( function() {
	
	this.log = function( message ) {
		if( typeof mediaWiki === 'undefined' ) {
			if( typeof console !== 'undefined' ) {
				console.log( 'Wikibase: ' + message );
			}
		}
		else {
			return mediaWiki.log.call( mediaWiki.log, 'Wikibase: ' + message );
		}
	}
	/*
	// Initialize user interface stuff when ready
	jQuery( function( $ ) {
		
	} );
	*/
} )();