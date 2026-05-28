// Reproduces Slim's $response->withJson($data, $status, JSON_NUMERIC_CHECK).
// JSON_NUMERIC_CHECK encodes numeric strings as numbers. We walk the value and
// convert any string that PHP's is_numeric() would treat as a number.
function isNumericString(s) {
  if (typeof s !== 'string' || s.length === 0) return false;
  // PHP is_numeric accepts optional leading sign, decimals, exponent, hex no.
  // For JSON_NUMERIC_CHECK we mimic typical PHP behaviour for ints/floats only.
  if (!/^-?(\d+(\.\d+)?|\.\d+)([eE][+-]?\d+)?$/.test(s)) return false;
  // PHP also skips numbers with leading zeros (e.g. "0123") — leave them alone.
  if (/^-?0\d+/.test(s)) return false;
  return true;
}

function coerce(v) {
  if (v === null || v === undefined) return v;
  if (typeof v === 'string') {
    return isNumericString(v) ? Number(v) : v;
  }
  if (Array.isArray(v)) return v.map(coerce);
  if (typeof v === 'object') {
    const out = {};
    for (const k of Object.keys(v)) out[k] = coerce(v[k]);
    return out;
  }
  return v;
}

function sendJson(res, data, status = 200) {
  res.status(status).type('application/json').send(JSON.stringify(coerce(data)));
}

module.exports = { sendJson, coerce };
