export type Env = {
	api_key: string;
	identifier: string;
	url: string;
};
export type Brief = {
	text: string;
	addressline1: string;
	addressline2: string;
	addressline3: string;
	addressline4: string;
	addressline5: string;
	created: string;
	hash?: string;
	error?: string;
	isSending?: boolean;
};
export type ProcessedStatus = 'success' | 'ignored';
export type Processed = {
	processedAt: string;
	hash: string;
	status?: ProcessedStatus;
};
