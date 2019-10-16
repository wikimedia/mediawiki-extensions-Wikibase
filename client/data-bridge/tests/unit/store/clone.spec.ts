import clone from '@/store/clone';

describe( 'clone', () => {
	it( 'clones a given object', () => {
		const org = {
			a: 'b',
			b: 0.2,
			d: [ 1, 2 ],
		};

		const dolly = clone( org );
		expect( dolly ).not.toBe( org );
		expect( dolly.d ).not.toBe( org.d );
		expect( dolly ).toStrictEqual( org );
	} );
} );
