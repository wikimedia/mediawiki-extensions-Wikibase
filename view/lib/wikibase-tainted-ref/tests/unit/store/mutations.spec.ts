import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_ALL_EDIT_MODE_FALSE,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
	SET_HELP_LINK,
	SET_STATEMENT_EDIT_TRUE,
	SET_STATEMENT_EDIT_FALSE,
} from '@/store/mutationTypes';
import { mutations } from '@/store/mutations';

describe( 'mutations', () => {
	it( 'should set the StatementsTaintedState in the store', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {} };
		mutations[ SET_ALL_UNTAINTED ]( state as any, [ 'foo', 'bar' ] );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: false, bar: false } );
	} );
	it( 'should set the StatementsPopperIsOpen in the store', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {} };
		mutations[ SET_ALL_POPPERS_HIDDEN ]( state as any, [ 'foo', 'bar' ] );
		expect( state ).toBeDefined();
		expect( state.statementsPopperIsOpen ).toEqual( { foo: false, bar: false } );
	} );
	it( 'should taint a single statement in the store', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {} };
		mutations[ SET_TAINTED ]( state as any, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: true } );
	} );
	it( 'should untaint a single statement in the store', () => {
		const state = { statementsTaintedState: { foo: true, bar: true }, statementsPopperIsOpen: {} };
		mutations[ SET_UNTAINTED ]( state as any, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsTaintedState ).toEqual( { foo: false, bar: true } );
	} );
	it( 'should hide a popper', () => {
		const state = { statementsPopperIsOpen: { foo: true, cat: true } };
		mutations[ SET_POPPER_HIDDEN ]( state as any, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsPopperIsOpen ).toEqual( { foo: false, cat: true } );
	} );
	it( 'should show a popper', () => {
		const state = { statementsPopperIsOpen: { burger: false, pizza: false } };
		mutations[ SET_POPPER_VISIBLE ]( state as any, 'pizza' );
		expect( state ).toBeDefined();
		expect( state.statementsPopperIsOpen ).toEqual( { burger: false, pizza: true } );
	} );
	it( 'should set the help link', () => {
		const state = { helpLink: 'foo' };
		mutations[ SET_HELP_LINK ]( state as any, 'wikidata/help' );
		expect( state ).toBeDefined();
		expect( state.helpLink ).toEqual( 'wikidata/help' );
	} );
	it( 'should set all the statementEditState in the store', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {}, statementsEditState: {} };
		mutations[ SET_ALL_EDIT_MODE_FALSE ]( state as any, [ 'foo', 'bar' ] );
		expect( state ).toBeDefined();
		expect( state.statementsEditState ).toEqual( { foo: false, bar: false } );
	} );
	it( 'should set the statementEditState in the store to true', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {}, statementsEditState: {} };
		mutations[ SET_STATEMENT_EDIT_TRUE ]( state as any, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsEditState ).toEqual( { foo: true } );
	} );
	it( 'should set the statementEditState in the store to false', () => {
		const state = { statementsTaintedState: {}, statementsPopperIsOpen: {}, statementsEditState: {} };
		mutations[ SET_STATEMENT_EDIT_FALSE ]( state as any, 'foo' );
		expect( state ).toBeDefined();
		expect( state.statementsEditState ).toEqual( { foo: false } );
	} );
} );
