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
import actions from '@/store/actions';
import {
	POPPER_HIDE, POPPER_SHOW,
	STORE_INIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
	HELP_LINK_SET,
	START_EDIT, STOP_EDIT,
} from '@/store/actionTypes';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';

describe( 'actions', () => {
	it( `commits to ${SET_ALL_UNTAINTED}, ${SET_ALL_POPPERS_HIDDEN} and ${SET_ALL_EDIT_MODE_FALSE}`, async () => {
		const context = newMockStore( {} );
		const payload = [ 'foo' ];
		await ( actions as Function )()[ STORE_INIT ]( context, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_UNTAINTED, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_POPPERS_HIDDEN, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_EDIT_MODE_FALSE, payload );
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
	it( `commits to ${SET_STATEMENT_EDIT_TRUE} and ${SET_POPPER_HIDDEN}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ START_EDIT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_STATEMENT_EDIT_TRUE, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_POPPER_HIDDEN, 'blah' );
	} );
	it( `commits to ${SET_STATEMENT_EDIT_FALSE}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ STOP_EDIT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_STATEMENT_EDIT_FALSE, 'blah' );
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
	it( `should commit to ${SET_HELP_LINK}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ HELP_LINK_SET ]( context, 'wikidata/help' );
		expect( context.commit ).toBeCalledWith( SET_HELP_LINK, 'wikidata/help' );
	} );
} );
