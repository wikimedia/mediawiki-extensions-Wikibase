import MwWindow, {
	OOUIWindow,
	WindowManager,
} from '@/@types/mediawiki/MwWindow';
import { initEvents } from '@/events';
import { EventEmitter } from 'events';

export default function subscribeToEvents( emitter: EventEmitter,
	windowManager: WindowManager,
	mwWindow: MwWindow ): void {
	emitter.on( initEvents.saved, () => {
		mwWindow.location.reload();
	} );
	emitter.on( initEvents.cancel, () => {
		windowManager.clearWindows().catch( () => { /* do nothing */ } );
	} );
	emitter.on( initEvents.reload, () => {
		mwWindow.location.reload();
	} );

	windowManager.on( 'closing', ( _win: OOUIWindow, compatClosing: JQuery.Promise<unknown> ) => {
		compatClosing.then( () => {
			windowManager.destroy();
		}, ( _e ) => {
			windowManager.destroy();
		} );
	} );
}
