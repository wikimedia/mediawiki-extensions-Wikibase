const { defineStore } = require( 'pinia' );

const useMessageStore = defineStore( 'message', {
	state: () => ( {
		messages: new Map(),
		messageCounter: 0
	} ),
	actions: {
		addStatusMessage( messageData ) {
			const newCount = this.messageCounter + 1;
			this.messageCounter = newCount;
			// Clear any active messages when we get a new message, per T429153
			// For now we continue to maintain a map of messages. We will clear
			// that up in T439118
			this.clearStatusMessages();
			this.messages.set( newCount, messageData );
			return newCount;
		},
		clearStatusMessage( messageId ) {
			if ( !this.messages.has( messageId ) ) {
				throw new RangeError( 'No such message ID: ' + messageId );
			}
			this.messages.delete( messageId );
		},
		clearStatusMessages() {
			this.messages.clear();
		},
		clearErrorMessages() {
			if ( this.messages.size > 0 ) {
				// As of T429153 there is only one message (or none)
				const onlyMessageKey = this.messages.keys().next().value;
				const message = this.messages.get( onlyMessageKey );
				if ( message.type === 'error' ) {
					this.clearStatusMessage( onlyMessageKey );
				}
			}
		}
	}
} );

module.exports = {
	useMessageStore
};
