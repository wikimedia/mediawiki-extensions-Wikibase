const { defineStore } = require( 'pinia' );

function setSnakValueHtmlForHash( store, hash, html ) {
	store.snakValues.set( hash, html );
	store.snakValuesWithErrors.delete( hash );

	if ( html.includes( 'cdx-message--error' ) ) {
		const dom = new DOMParser().parseFromString( html, 'text/html' );
		if ( dom.querySelector( '.cdx-message--error' ) !== null ) {
			store.snakValuesWithErrors.add( hash );
		}
	}
}

const useServerRenderedHtml = defineStore( 'serverRenderedHtml', {
	state: () => ( {
		propertyLinks: new Map(),
		snakValues: new Map(),
		snakValuesWithErrors: new Set()
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
				const html = snakValue.querySelector( 'span.snakValue' ).innerHTML;
				if ( this.snakValues.has( snakHash ) ) {
					const previousHtml = this.snakValues.get( snakHash );
					if ( previousHtml !== html ) {
						mw.log.warn(
							`Inconsistent server-rendered HTML for snak with hash ${ snakHash }:\n` +
							`${ previousHtml } != ${ html }`
						);
					}
				} else {
					setSnakValueHtmlForHash( this, snakHash, html );
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
 * Update link html for one or more properties
 *
 * @param {Object} propertyHtmlStrings a mapping of property ids to their link Html
 */
function updatePropertyLinkHtml( propertyHtmlStrings ) {
	if ( typeof propertyHtmlStrings !== 'object' ) {
		return;
	}
	const serverRenderedHtml = useServerRenderedHtml();
	for ( const [ propertyId, html ] of Object.entries( propertyHtmlStrings ) ) {
		serverRenderedHtml.propertyLinks.set( propertyId, html );
	}
}

/**
 * Return the HTML for the value/somevalue/novalue part of the snak with the given hash.
 * Does not include the property.
 *
 * @param {string} hash
 * @returns {string|undefined} HTML
 */
function snakValueHtmlForHash( hash ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.snakValues.get( hash );
}

function updateSnakValueHtmlForHash( hash, html ) {
	const serverRenderedHtml = useServerRenderedHtml();
	setSnakValueHtmlForHash( serverRenderedHtml, hash, html );
}

/**
 * Check whether the HTML for the snak with the given hash contains an error message.
 *
 * @param {string} hash
 * @returns {boolean}
 */
function snakValueHtmlForHashHasError( hash ) {
	const serverRenderedHtml = useServerRenderedHtml();
	return serverRenderedHtml.snakValuesWithErrors.has( hash );
}

module.exports = {
	useServerRenderedHtml,
	propertyLinkHtml,
	updatePropertyLinkHtml,
	snakValueHtmlForHash,
	updateSnakValueHtmlForHash,
	snakValueHtmlForHashHasError
};
