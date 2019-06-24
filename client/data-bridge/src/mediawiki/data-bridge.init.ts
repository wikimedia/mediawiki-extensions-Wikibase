import selectLinks from './selectLinks';

function countLinks(): void {
	// eslint-disable-next-line no-console
	console.log( `Number of links potentially usable for data bridge: ${ selectLinks().length }` );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', countLinks );
} else {
	countLinks();
}
