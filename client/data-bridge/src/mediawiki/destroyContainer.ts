import { WindowManager } from '@/@types/mediawiki/MwWindow';

export default function destroyContainer( windowManager: WindowManager ): void {
	windowManager.clearWindows().then( () => {
		windowManager.destroy();
	},
	( _e ) => {
		windowManager.destroy();
	} );
}
