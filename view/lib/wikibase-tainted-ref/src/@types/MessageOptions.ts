export type MessageToTextFunction = ( key: string ) => string;

export default interface MessageOptions {
	messageToTextFunction: MessageToTextFunction;
}
