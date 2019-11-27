export default interface MessagesRepository {
	get( messageKey: string, ...params: ( string| HTMLElement )[] ): string;
}
