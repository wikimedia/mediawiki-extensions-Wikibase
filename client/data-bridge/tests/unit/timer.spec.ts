import { waitFor } from './../util/timer';

describe( 'waitFor', () => {
	it( 'waits for a given condition', () => {
		let swap = 0;

		setTimeout( () => {
			swap = 100;
		}, 200 );

		return waitFor( () => {
			return swap === 100;
		} ).then( () => {
			expect( swap ).toBe( 100 );
		} );
	} );

	it( 'rejects on timeout', async () => {
		await expect( waitFor( () => {
			return false;
		} ) ).rejects.toStrictEqual( Error( 'Test timed out in 300ms.' ) );
	} );
} );
