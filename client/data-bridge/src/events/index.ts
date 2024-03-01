export enum initEvents {
	saved = 'saved',
	cancel = 'cancel',
	reload = 'reload',
}

export enum appEvents {
	relaunch = 'relaunch',
	redirect = 'redirect',
}

export default {
	...initEvents,
	...appEvents,
};
