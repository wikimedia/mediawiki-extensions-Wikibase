const { defineStore } = require( 'pinia' );

const useServerRenderedHtml = defineStore( 'serverRenderedHtml', {
	state: () => ( {
		propertyLinks: new Map(),
		snakValues: new Map()
	} ),
	actions: {
		/**
		 * @param {HTMLElement} element
		 * @return {void}
		 */
		importFromElement( element ) {
			for ( const propertyLink of element.getElementsByClassName( 'wikibase-wbui2025-property-name-link' ) ) {
				const propertyId = propertyLink.dataset.propertyId;
				const linkHtml = propertyLink.innerHTML;
				if ( this.propertyLinks.has( propertyId ) ) {
					const previousLinkHtml = this.propertyLinks.get( propertyId );
					if ( previousLinkHtml !== linkHtml ) {
						mw.log.warn(
							`Inconsistent server-rendered HTML for link to property ${ propertyId }:\n` +
							`${ previousLinkHtml } != ${ linkHtml }`
						);
					}
				} else {
					this.propertyLinks.set( propertyId, linkHtml );
				}
			}
			for ( const snakValue of element.getElementsByClassName( 'wikibase-wbui2025-snak-value' ) ) {
				const snakHash = snakValue.dataset.snakHash;
				const html = snakValue.innerHTML;
				if ( this.snakValues.has( snakHash ) ) {
					const previousHtml = this.snakValues.get( snakHash );
					if ( previousHtml !== html ) {
						mw.log.warn(
							`Inconsistent server-rendered HTML for snak with hash ${ snakHash }:\n` +
							`${ previousHtml } != ${ html }`
						);
					}
				} else {
					this.snakValues.set( snakHash, html );
				}
			}
		}
	}
} );

/**
 * @param {string} propertyId
 * @returns {string|undefined} HTML
 */
function propertyLinkHtml( propertyId ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.propertyLinks.get( propertyId );
}

/**
 * Return the HTML for the value/somevalue/novalue part of the given snak.
 * Does not include the property.
 *
 * @param {Object} snak
 * @returns {string|undefined} HTML
 */
function snakValueHtml( snak ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.snakValues.get( snak.hash );
}

module.exports = {
	useServerRenderedHtml,
	propertyLinkHtml,
	snakValueHtml
};
