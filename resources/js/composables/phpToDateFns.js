const PHP_TO_DATE_FNS = {
	d: "dd",
	j: "d",
	D: "EEE",
	l: "EEEE",
	m: "MM",
	n: "M",
	M: "MMM",
	F: "MMMM",
	y: "yy",
	Y: "yyyy",
	H: "HH",
	G: "H",
	h: "hh",
	g: "h",
	i: "mm",
	s: "ss",
	A: "a",
	a: "a",
};

/**
 * Translate a PHP date() format string into a date-fns format string.
 * Unmapped letters are escaped (single-quoted) so date-fns never throws on
 * an unknown token; non-letters pass through; `\X` is a PHP literal escape.
 */
export function phpToDateFns(phpFormat) {
	let result = "";
	for (let i = 0; i < phpFormat.length; i++) {
		const ch = phpFormat[i];
		if (ch === "\\") {
			const next = phpFormat[i + 1] ?? "";
			if (next) {
				result += `'${next}'`;
				i++;
			}
			continue;
		}
		if (Object.prototype.hasOwnProperty.call(PHP_TO_DATE_FNS, ch)) {
			result += PHP_TO_DATE_FNS[ch];
		} else if (/[A-Za-z]/.test(ch)) {
			result += `'${ch}'`;
		} else {
			result += ch;
		}
	}
	return result;
}
