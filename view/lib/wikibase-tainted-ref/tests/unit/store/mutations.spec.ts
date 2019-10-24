import { SET_ALL_UNTAINTED, SET_TAINTED, SET_UNTAINTED } from '@/store/mutationTypes';
import { mutations } from '@/store/mutations';

describe( 'mutations', () => {
	it( 'should set the StatementsTaintedState in the store', () => {
		const state = { statementsTaintedState: {} };
		mutations[ SET_ALL_UNTAINTED ]( state, [ 'foo', 'bar' ] );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: false, bar: false } );
	} );
	it( 'should taint a single statement in the store', () => {
		const state = { statementsTaintedState: {} };
		mutations[ SET_TAINTED ]( state, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: true } );
	} );
	it( 'should untaint a single statement in the store', () => {
		const state = { statementsTaintedState: { foo: true, bar: true } };
		mutations[ SET_UNTAINTED ]( state, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: false, bar: true } );
	} );
} );
