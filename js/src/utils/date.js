/**
 * Unix seconds for the start of a day (00:00:00) in local time.
 */
export function startOfDay(date) {
	return Math.floor(new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0).getTime() / 1000)
}

/**
 * Unix seconds for the end of a day (23:59:59) in local time.
 */
export function endOfDay(date) {
	return Math.floor(new Date(date.getFullYear(), date.getMonth(), date.getDate(), 23, 59, 59).getTime() / 1000)
}
