import { WindowManager } from '@/@types/mediawiki/MwWindow';
import destroyContainer from '@/mediawiki/destroyContainer';
import Events from '@/events';
import { EventEmitter } from 'events';

export default function subscribeToAppEvents( emitter: EventEmitter, windowManager: WindowManager ): void {
	emitter.on( Events.onSaved, () => {
		destroyContainer( windowManager );
	} );
	emitter.on( Events.onCancel, () => {
		destroyContainer( windowManager );
	} );
}
