import { WindowManager } from '@/@types/mediawiki/MwWindow';
import destroyContainer from '@/mediawiki/destroyContainer';

describe( 'destroyContainer', () => {
	it( 'calls clearWindows of the given OOUI window manager', () => {
		const ooWindow = {
			clearWindows: jest.fn( () => Promise.resolve() ),
			destroy: jest.fn(),
		} as unknown as WindowManager;

		destroyContainer( ooWindow );
		expect( ooWindow.clearWindows ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls destroy on resolving clearWindows', async () => {
		const ooWindow = {
			clearWindows: jest.fn( () => Promise.resolve() ),
			destroy: jest.fn(),
		} as unknown as WindowManager;

		await destroyContainer( ooWindow );
		expect( ooWindow.destroy ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'calls destroy on rejecting clearWindows', async () => {
		const ooWindow = {
			clearWindows: jest.fn( () => Promise.reject( new Error( 'no' ) ) ),
			destroy: jest.fn(),
		} as unknown as WindowManager;

		await destroyContainer( ooWindow );
		expect( ooWindow.destroy ).toHaveBeenCalledTimes( 1 );
	} );
} );
