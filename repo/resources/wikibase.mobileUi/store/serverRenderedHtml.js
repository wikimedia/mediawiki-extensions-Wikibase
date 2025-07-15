const { defineStore } = require( 'pinia' );

const useServerRenderedHtml = defineStore( 'serverRenderedHtml', {
	state: () => ( {
		snakHtmls: new Map()
	} ),
	actions: {
		/**
		 * @param {HTMLElement} element
		 * @return {void}
		 */
		importFromElement( element ) {
			for ( const snak of element.getElementsByClassName( 'wikibase-mex-snak-value' ) ) {
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

function snakHtml( snak ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.snakHtmls.get( snak.hash );
}

module.exports = {
	useServerRenderedHtml,
	snakHtml
};
