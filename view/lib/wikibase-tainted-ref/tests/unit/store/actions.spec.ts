import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
} from '@/store/mutationTypes';
import actions from '@/store/actions';
import {
	POPPER_HIDE, POPPER_SHOW,
	STORE_INIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
} from '@/store/actionTypes';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';

describe( 'actions', () => {
	it( `commits to ${SET_ALL_UNTAINTED} and ${SET_ALL_POPPERS_HIDDEN}`, async () => {
		const context = newMockStore( {} );
		const payload = [ 'foo' ];
		await ( actions as Function )()[ STORE_INIT ]( context, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_UNTAINTED, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_POPPERS_HIDDEN, payload );
	} );
	it( `commits to ${SET_UNTAINTED} and ${SET_POPPER_HIDDEN}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_UNTAINT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_UNTAINTED, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_POPPER_HIDDEN, 'blah' );
	} );
	it( `commits to ${SET_TAINTED}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_TAINT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_TAINTED, 'blah' );
	} );
	it( `should commit to ${SET_POPPER_HIDDEN}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ POPPER_HIDE ]( context, 'potato' );
		expect( context.commit ).toBeCalledWith( SET_POPPER_HIDDEN, 'potato' );
	} );
	it( `should commit to ${SET_POPPER_VISIBLE}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ POPPER_SHOW ]( context, 'duck' );
		expect( context.commit ).toBeCalledWith( SET_POPPER_VISIBLE, 'duck' );
	} );
} );
