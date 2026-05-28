function verifyRequiredParams(params, required) {
  const missing = [];
  const src = params || {};
  for (const f of required) {
    const v = src[f];
    if (v === undefined || v === null || String(v).trim().length === 0) {
      missing.push(f);
    }
  }
  if (missing.length) {
    return {
      error: true,
      message: 'Required field(s) ' + missing.join(', ') + ' is missing or empty',
    };
  }
  return {};
}

function isEmail(s) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(s || ''));
}
function isPhoneNumber(s) {
  return /^[0-9]{6,20}$/.test(String(s || ''));
}
function isName(s) {
  return /^[0-9a-zA-Z\s.]{3,25}$/.test(String(s || ''));
}
function isAddress(s) {
  return /^[A-Za-z0-9/#\-_()\s.,:&]{3,120}$/.test(String(s || ''));
}
function validateDate(date, format = 'Y-m-d') {
  // matches PHP DateTime::createFromFormat('Y-m-d', ...)
  if (format !== 'Y-m-d') return !isNaN(Date.parse(date));
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(date || ''));
  if (!m) return false;
  const [_, y, mo, d] = m;
  const dt = new Date(`${y}-${mo}-${d}T00:00:00Z`);
  return (
    dt.getUTCFullYear() === +y &&
    dt.getUTCMonth() + 1 === +mo &&
    dt.getUTCDate() === +d
  );
}

module.exports = {
  verifyRequiredParams,
  isEmail,
  isPhoneNumber,
  isName,
  isAddress,
  validateDate,
};
