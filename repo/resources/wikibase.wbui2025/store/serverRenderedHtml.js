const { defineStore } = require( 'pinia' );

const useServerRenderedHtml = defineStore( 'serverRenderedHtml', {
	state: () => ( {
		propertyLinks: new Map(),
		snakHtmls: new Map()
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
			for ( const snak of element.getElementsByClassName( 'wikibase-wbui2025-snak-value' ) ) {
				const snakHash = snak.dataset.snakHash;
				const html = snak.innerHTML;
				if ( this.snakHtmls.has( snakHash ) ) {
					const previousHtml = this.snakHtmls.get( snakHash );
					if ( previousHtml !== html ) {
						mw.log.warn(
							`Inconsistent server-rendered HTML for snak with hash ${ snakHash }:\n` +
							`${ previousHtml } != ${ html }`
						);
					}
				} else {
					this.snakHtmls.set( snakHash, html );
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

function snakHtml( snak ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.snakHtmls.get( snak.hash );
}

module.exports = {
	useServerRenderedHtml,
	propertyLinkHtml,
	snakHtml
};
