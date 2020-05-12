module.exports = {
	/**
	 * Switch to the other browser window (or tab), assuming that
	 * there is exactly one other window; execute the callback there;
	 * then close the window and return to the original one. This
	 * helper should be used immediately after an action that opens a
	 * new window/tab, such as clicking a link that has target=_blank.
	 *
	 * WebDriver’s target window seems to be unrelated to the current
	 * / foreground window or tab of the browser. After clicking a
	 * link that opens in a new tab, the browser will show that tab,
	 * but WebDriver commands will continue to target the original
	 * window. Additionally, closing the current window does not
	 * switch the target back to the original window. That’s why this
	 * function has to do some extra juggling to manage the current
	 * window, including in case of an error (to prevent the extra
	 * window from polluting other tests).
	 */
	doInOtherWindow( callback ) {
		const originalWindow = browser.getWindowHandle(),
			allWindows = browser.getWindowHandles(),
			[ otherWindow, ...extraWindows ] = allWindows.filter( ( window ) => window !== originalWindow );
		if ( !otherWindow ) {
			throw new Error( 'There is no other window!' );
		}
		if ( extraWindows.length > 0 ) {
			// order of handles is arbitrary so we have no idea which to target
			throw new Error( 'There is more than one other window!' );
		}
		browser.switchToWindow( otherWindow );
		try {
			callback();
		} finally {
			browser.closeWindow();
			browser.switchToWindow( originalWindow );
		}
	},
};
