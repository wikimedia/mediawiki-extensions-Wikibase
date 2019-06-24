import selectLinks from './selectLinks';

function countLinks() {
	const validLinks = selectLinks();
	// eslint-disable-next-line no-console
	console.log( `Number of links potentially usable for data bridge: ${validLinks.length}` );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', countLinks );
} else {
	countLinks();
}
