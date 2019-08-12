import namespacedStoreEvent from '@/store/namespacedStoreEvent';

describe( 'namespacedStoreEvent', () => {
	it( 'concartinates namespaces and store events', () => {
		expect( namespacedStoreEvent( 'foo', 'bar' ) ).toBe( 'foo/bar' );
		expect( namespacedStoreEvent( 'foo', 'bar', 'baz' ) ).toBe( 'foo/bar/baz' );
	} );
} );
