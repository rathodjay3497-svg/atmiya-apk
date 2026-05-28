const jwt = require('jsonwebtoken');

// Mirrors the Slim JwtAuthentication "passthrough" list in src/middleware.php.
const PASSTHROUGH = new Set([
  '/login',
  '/changePassword',
  '/getYuvakByTeam',
  '/getEduOptions',
  '/getTeamsOption',
  '/addYuvak',
  '/updateYuvak',
  '/addRemarks',
  '/updateRemarks',
  '/getRemarkByYuvak',
  '/getOnlyKK',
  '/changeTeam',
  '/updateLongLate',
  '/addSabha',
  '/updateSabha',
  '/getSabhaByKK',
  '/getAttYuvak',
  '/updateAtt',
  '/sendAllNotification',
  '/sendSabhaNotification',
  '/sendBdayNotification',
  '/getRandomYuvak',
  '/getAltersByKK',
  '/getUpcomingBday',
  '/getConditons',
  '/addNewTaskByYuvak',
  '/updateTaskByYuvak',
  '/getAllTaskByYuvak',
  '/getAllTaskByKK',
  '/yuvakXlReport',
  '/updateLogin',
  '/insertDbManual',
  '/sendBdayNotificationToKK',
  '/yuvakSabhaReport',
  '/getSearchYuvak',
  '/defaultMsg',
  '/insertDefaultMsg',
  '/getPresentYuvak',
  '/yuvakXlPadhramniReport',
  '/getMentorTeams',
  '/getYuvakCategoryOptions',
  '/getYuvakByCategory',
]);

function jwtMiddleware(req, res, next) {
  if (PASSTHROUGH.has(req.path)) return next();
  const auth = req.headers.authorization || '';
  const m = /^Bearer\s+(\S+)$/.exec(auth);
  if (!m) {
    return res
      .status(401)
      .type('application/json')
      .send(JSON.stringify({ error: true, message: 'Token not found.' }, null, 2));
  }
  try {
    const decoded = jwt.verify(m[1], process.env.JWT_SECRET, { algorithms: ['HS256'] });
    req.jwt = decoded;
    next();
  } catch (e) {
    res
      .status(401)
      .type('application/json')
      .send(JSON.stringify({ error: true, message: e.message }, null, 2));
  }
}

// Matches helper.php is_jwt_valid(): verifies the bearer token on demand,
// for routes in the passthrough list that still re-check inside the handler.
function isJwtValid(req) {
  const auth = req.headers.authorization || '';
  const m = /^Bearer\s+(\S+)$/.exec(auth);
  if (!m) return false;
  try {
    jwt.verify(m[1], process.env.JWT_SECRET, { algorithms: ['HS256'] });
    return true;
  } catch {
    return false;
  }
}

module.exports = { jwtMiddleware, isJwtValid, PASSTHROUGH };
