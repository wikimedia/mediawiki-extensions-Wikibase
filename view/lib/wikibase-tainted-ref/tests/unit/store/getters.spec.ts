import { getters } from '@/store/getters';
import { GET_EDIT_STATE, GET_HELP_LINK, GET_POPPER_STATE, GET_STATEMENT_TAINTED_STATE } from '@/store/getterTypes';

describe( 'getters', () => {
	describe( 'statementsTaintedState', () => {
		it( 'should return a function that returns the tainted state for a given guid', () => {
			const mockState = { statementsTaintedState: {
				foo: false,
				bar: true,
			}, statementsPopperIsOpen: {} };
			const getStatementsTaintedState = getters[ GET_STATEMENT_TAINTED_STATE ](
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
	describe( 'statementsEditState', () => {
		it( 'should return a function that returns the edit state for a given guid', () => {
			const mockState = {
				statementsTaintedState: {},
				statementsPopperIsOpen: {},
				statementsEditState: {
					foo: false,
					bar: true,
				},
			};
			const getStatementsEditState = getters[ GET_EDIT_STATE ](
				mockState as any,
				{},
				mockState as any,
				{},
			);
			expect( getStatementsEditState ).toBeDefined();
			expect( getStatementsEditState( 'bar' ) ).toBeTruthy();
			expect( getStatementsEditState( 'foo' ) ).toBeFalsy();
		} );
	} );
	describe( 'popperState ', () => {
		it( 'should return a function that returns the popper open or closed state', () => {
			const mockState = { statementsPopperIsOpen: {
				foo: false,
				bar: true,
			} };
			const getPopperState = getters[ GET_POPPER_STATE ]( mockState as any, {}, mockState as any, {} );
			expect( getPopperState ).toBeDefined();
			expect( getPopperState( 'bar' ) ).toBeTruthy();
			expect( getPopperState( 'foo' ) ).toBeFalsy();
		} );
	} );
	describe( 'helpLink ', () => {
		it( 'should return a function that returns the popper open or closed state', () => {
			const mockState = { helpLink: 'http://wikidatafoo/Help' };
			const getHelpLink = getters[ GET_HELP_LINK ]( mockState as any, {}, mockState as any, {} );
			expect( getHelpLink ).toBeDefined();
			expect( getHelpLink ).toEqual( 'http://wikidatafoo/Help' );
		} );
	} );
} );
