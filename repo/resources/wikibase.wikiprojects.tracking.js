/**
 * Add tracking to Wikiproject links (T421856)
 *
 * @license GPL-2.0-or-later
 */
( function ( mw ) {
	'use strict';

	/**
	 * Adds tracking of user clicks on wikiproject links if the `wikiproject-link-click-tracker` instrument
	 * is enabled and this interaction is within the sample.
	 *
	 * @param {jQuery} $content
	 */
	mw.hook( 'wikipage.content' ).add( () => {
		/** @type {mw.testKitchen.InstrumentInterface|undefined} */
		const wikiprojectLinkInstrument = mw.testKitchen && mw.testKitchen.getInstrument( 'wikiproject-link-click-tracker' );
		if ( wikiprojectLinkInstrument && wikiprojectLinkInstrument.isInSample() ) {
			$( '*[data-mw-tracking-link-type="wikiproject"]' ).on( 'click', ( event ) => {
				/* eslint-disable camelcase */
				wikiprojectLinkInstrument.send( 'click', {
					action_source: 'wbFromWikidataClick',
					action_context: JSON.stringify( {
						project_url: event.currentTarget.href,
						source_entity: $( event.currentTarget ).data( 'mwSourceEntityId' )
					} )
				} );
				/* eslint-enable camelcase */
			} );
		}
	} );

}( mw ) );
