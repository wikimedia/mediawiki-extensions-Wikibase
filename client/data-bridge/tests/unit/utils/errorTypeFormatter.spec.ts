import errorTypeFormatter from '@/utils/errorTypeFormatter';

describe( 'errorTypeFormatter', () => {
	it.each( [
		[ 'lower_snake_case', 'lower_snake_case' ],
		[ 'UPPER_SNAKE_CASE', 'upper_snake_case' ],
		[ 'lower-kebab-case', 'lower_kebab_case' ],
		[ 'UPPER-KEBAB-CASE', 'upper_kebab_case' ],
		[ '*consecutive* non-letters', 'consecutive_non_letters' ],
		[ '!!!extra important!!!', 'extra_important' ],
		[ 'error 502', 'error_502' ],
	] )( 'maps type %s to %s', async ( type: string, expectedType: string ) => {
		expect( errorTypeFormatter( type ) ).toBe( expectedType );
	} );
} );
