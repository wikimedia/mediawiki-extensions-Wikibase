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
		}
	}
} );

module.exports = {
	useMessageStore
};
