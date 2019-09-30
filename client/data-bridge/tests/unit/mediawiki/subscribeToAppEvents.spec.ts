import subscribeToAppEvents from '@/mediawiki/subscribeToAppEvents';
import Events from '@/events';
import { EventEmitter } from 'events';

const mockDestroyContainer = jest.fn();
jest.mock( '@/mediawiki/destroyContainer', () => ( {
	__esModule: true,
	default: ( windowManager: any ) => mockDestroyContainer( windowManager ),
} ) );

describe( 'subscribeToAppEvents', () => {
	describe( Events.onSaved, () => {
		it( 'subscribes on saved', () => {
			const emitter = { on: jest.fn() };

			subscribeToAppEvents( emitter as any, {} as any );
			expect( emitter.on.mock.calls[ 0 ][ 0 ] ).toBe( Events.onSaved );
		} );

		it( 'calls destroyContainer on emitted event', () => {
			const emitter = new EventEmitter();
			const ooWindow = jest.fn();

			subscribeToAppEvents( emitter, ooWindow as any );
			emitter.emit( Events.onSaved );

			expect( mockDestroyContainer ).toHaveBeenCalledWith( ooWindow );
		} );
	} );

	describe( Events.onCancel, () => {
		it( 'subscribes on cancel', () => {
			const emitter = { on: jest.fn() };

			subscribeToAppEvents( emitter as any, {} as any );
			expect( emitter.on.mock.calls[ 1 ][ 0 ] ).toBe( Events.onCancel );
		} );

		it( 'calls destroyContainer on emitted event', () => {
			const emitter = new EventEmitter();
			const ooWindow = jest.fn();

			subscribeToAppEvents( emitter, ooWindow as any );
			emitter.emit( Events.onCancel );

			expect( mockDestroyContainer ).toHaveBeenCalledWith( ooWindow );
		} );
	} );
} );
