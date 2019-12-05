import { getters } from '@/store/getters';

describe( 'getters', () => {
	describe( 'statementsTaintedState', () => {
		it( 'should return a function that returns the tainted state for a given guid', () => {
			const mockState = { statementsTaintedState: {
				foo: false,
				bar: true,
			}, statementsPopperIsOpen: {} };
			const getStatementsTaintedState = getters.statementsTaintedState(
				mockState as any,
				{},
				mockState as any,
				{},
			);
			expect( getStatementsTaintedState ).toBeDefined();
			expect( getStatementsTaintedState( 'bar' ) ).toBeTruthy();
			expect( getStatementsTaintedState( 'foo' ) ).toBeFalsy();
		} );
	} );
	describe( 'popperState ', () => {
		it( 'should return a function that returns the popper open or closed state', () => {
			const mockState = { statementsPopperIsOpen: {
				foo: false,
				bar: true,
			} };
			const getPopperState = getters.popperState( mockState as any, {}, mockState as any, {} );
			expect( getPopperState ).toBeDefined();
			expect( getPopperState( 'bar' ) ).toBeTruthy();
			expect( getPopperState( 'foo' ) ).toBeFalsy();
		} );
	} );
	describe( 'helpLink ', () => {
		it( 'should return a function that returns the popper open or closed state', () => {
			const mockState = { helpLink: 'http://wikidatafoo/Help' };
			const getHelpLink = getters.helpLink( mockState as any, {}, mockState as any, {} );
			expect( getHelpLink ).toBeDefined();
			expect( getHelpLink ).toEqual( 'http://wikidatafoo/Help' );
		} );
	} );
} );