/**
 * "YYYY-MM-DD" in LOCAL time.
 * toISOString() always returns UTC, which gives the wrong calendar date
 * for users east of UTC (or near midnight anywhere).
 */
export function localDateStr(date) {
	const y = date.getFullYear()
	const m = String(date.getMonth() + 1).padStart(2, '0')
	const d = String(date.getDate()).padStart(2, '0')
	return `${y}-${m}-${d}`
}

/**
 * "YYYY-MM-DDTHH:MM" in LOCAL time, suitable for <input type="datetime-local">.
 */
export function localDatetimeStr(date) {
	const h = String(date.getHours()).padStart(2, '0')
	const min = String(date.getMinutes()).padStart(2, '0')
	return `${localDateStr(date)}T${h}:${min}`
}

/**
 * Unix seconds for LOCAL midnight of a "YYYY-MM-DD" string.
 * new Date('YYYY-MM-DD') is parsed as UTC midnight per spec —
 * appending 'T00:00:00' (no tz) makes the browser treat it as local time.
 */
export function dateStrToUnixStart(dateStr) {
	return Math.floor(new Date(dateStr + 'T00:00:00').getTime() / 1000)
}

/**
 * Unix seconds for LOCAL 23:59:59 of a "YYYY-MM-DD" string.
 */
export function dateStrToUnixEnd(dateStr) {
	return Math.floor(new Date(dateStr + 'T23:59:59').getTime() / 1000)
}
