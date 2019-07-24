import 'url-search-params-polyfill';
/**
 * Get the value of the current (i.e. in browser) URL's GET param
 * or redirect to the given default value if not present
 *
 * @param name string
 * @param defaultValue string
 */
export default function ( name: string, defaultValue: string ): string|void {
	const searchParams = new URLSearchParams( location.search );
	const value = searchParams.get( name );
	if ( value === null || value.trim() === '' ) {
		searchParams.set( name, defaultValue );
		window.open( `?${searchParams.toString()}`, '_self' );
		return;
	}
	return value;
}
