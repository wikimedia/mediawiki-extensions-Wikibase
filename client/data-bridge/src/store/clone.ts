export default function clone<T>( source: T ): T {
	return JSON.parse( JSON.stringify( source ) );
}
