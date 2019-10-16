import { SET_ALL_TAINTED, SET_UNTAINTED } from '@/store/mutationTypes';
import { mutations } from '@/store/mutations';

describe( 'mutations', () => {
	it( 'should set the StatementsTaintedState in the store', () => {
		const state = { statementsTaintedState: {} };
		mutations[ SET_ALL_TAINTED ]( state, [ 'foo', 'bar' ] );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: true, bar: true } );
	} );
	it( 'should untaint a single statement in the store', () => {
		const state = { statementsTaintedState: {} };
		mutations[ SET_ALL_TAINTED ]( state, [ 'foo', 'bar' ] );
		mutations[ SET_UNTAINTED ]( state, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: false, bar: true } );
	} );
} );
