import { selectLinks, filterLinksByHref } from './selectLinks';

function countLinks() {
	// eslint-disable-next-line no-console
	console.log( `Number of links potentially usable for data bridge: ${ filterLinksByHref( selectLinks() ).length }` );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', countLinks );
} else {
	countLinks();
}
