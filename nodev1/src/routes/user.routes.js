// 1:1 port of routersv1/user.router.php. Each route mirrors the same validation,
// response keys, status codes, and post-processing as the Slim PHP handler.

const express = require('express');
const jwt = require('jsonwebtoken');
const { User } = require('../services/user.service');
const { verifyRequiredParams } = require('../helpers/validate');
const { sendJson } = require('../helpers/response');
const { isJwtValid } = require('../middleware/jwt');
const { sendToTopic } = require('../helpers/firebase');
const { upload } = require('../helpers/upload');

const router = express.Router();

function firebaseSend({ topic, title, subtitle }) {
  return sendToTopic(topic, title, subtitle);
}

const unauthorized = { error: true, message: 'Unauthorized accessed.' };
/**
 * @openapi
 * /login:
 *   post:
 *     summary: Log in to the application
 *     tags: [Authentication]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             required:
 *               - mobile
 *               - password
 *             properties:
 *               mobile:
 *                 type: string
 *                 example: "9988776655"
 *               password:
 *                 type: string
 *                 example: "3690@Pjda$"
 *     responses:
 *       200:
 *         description: Login response containing access token and user information
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 */
router.post('/login', async (req, res) => {
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['mobile', 'password']);
  if (Object.keys(responseArr).length === 0) {
    const u = new User();
    const r = await u.checkLogin(input.mobile, input.password);
    if (r.error === false) {
      const future = Math.floor(Date.now() / 1000) + 365 * 24 * 60 * 60;
      const payload = {
        iat: Math.floor(Date.now() / 1000),
        jti: Buffer.from(String(input.mobile)).toString('hex'),
        scope: 'private',
        exp: future,
        sub: { mobile: input.mobile },
      };
      const token = jwt.sign(payload, process.env.JWT_SECRET, { algorithm: 'HS256' });
      r.token = token;
      responseArr = r;
    } else {
      responseArr = { error: true, message: 'Invalid Mobile Number or Password.' };
    }
  }
  sendJson(res, responseArr, 200);
});
/**
 * @openapi
 * /changePassword:
 *   post:
 *     summary: Change user password
 *     tags: [Authentication]
 *     security:
 *       - BearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             required:
 *               - yid
 *               - password
 *             properties:
 *               yid:
 *                 type: integer
 *                 example: 123
 *               password:
 *                 type: string
 *                 example: "newsecurepassword"
 *     responses:
 *       200:
 *         description: Success or failure of modification
 *       401:
 *         description: Unauthorized
 */
router.post('/changePassword', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'password']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().changePassword(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateLogin', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'status']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateLogin(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getConditons', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const r = await new User().getConditons();
  if (r && Object.keys(r).length) {
    sendJson(res, { error: false, data: r }, 200);
  } else {
    sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
  }
});

function summarizeYuvakTotals(res) {
  let totalCount = res.length;
  let totalRegular = 0;
  let totalIrregular = 0;
  for (const v of res) {
    totalCount += Number(v.totalSubYuvak || 0);
    totalRegular += Number(v.subRegular || 0);
    totalIrregular += Number(v.subIrregular || 0);
    if (v.sabhaSta == 0) totalIrregular += 1;
    else if (v.sabhaSta == 1) totalRegular += 1;
  }
  return { totalCount, totalRegular, totalIrregular };
}

router.post('/getYuvakByTeam', async (req, res) => {
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_level', 'kk_id']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getYuvakByTeam(input);
    if (r.length !== 0) {
      const sums = summarizeYuvakTotals(r);
      responseArr = { error: false, ...sums, data: r };
    } else {
      responseArr = { error: true, message: 'Not Data Found.', data: [] };
    }
  }
  sendJson(res, responseArr, 200);
});

router.post('/getYuvakByCategory', async (req, res) => {
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_level', 'kk_id', 'type']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getYuvakByCategory(input);
    if (r.length !== 0) {
      const sums = summarizeYuvakTotals(r);
      responseArr = { error: false, ...sums, data: r };
    } else {
      responseArr = { error: true, message: 'Not Data Found.', data: [] };
    }
  }
  sendJson(res, responseArr, 200);
});

router.post('/getSearchYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_level', 'tid', 'query', 'type']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getSearchYuvak(input);
    if (r.length !== 0) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.', data: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getEduOptions', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const r = await new User().getEduOptions();
  if (r.length !== 0) sendJson(res, { error: false, data: r }, 200);
  else sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
});

router.post('/getYuvakCategoryOptions', async (req, res) => {
  const r = await new User().getYuvakCategoryOptions();
  if (r.length !== 0) sendJson(res, { error: false, data: r }, 200);
  else sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
});

router.post('/getTeamsOption', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const r = await new User().getTeamsOption();
  if (r.length !== 0) sendJson(res, { error: false, data: r }, 200);
  else sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
});

router.post('/addYuvak', upload.single('image'), async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, [
    'isImage',
    'name',
    'mobile',
    'tid',
    'kk_level',
    'kk_id',
    'eid',
  ]);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().addYuvak(input, req.file);
    responseArr = ok
      ? { error: false, message: 'Data Added.' }
      : { error: true, message: 'Data Not Added.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateYuvak', upload.single('image'), async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, [
    'isImage',
    'name',
    'mobile',
    'tid',
    'kk_level',
    'kk_id',
    'eid',
  ]);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().updateYuvak(input, req.file);
    if (r === true) responseArr = { error: false, message: 'Data Modified.' };
    else if (r === 'mobile')
      responseArr = { error: true, message: 'Other user used the same mobile number.' };
    else responseArr = { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/addRemarks', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'rel']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().addRemarks(input);
    responseArr = ok
      ? { error: false, message: 'Data Added.' }
      : { error: true, message: 'Data Not Added.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateRemarks', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['rid', 'rel']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateRemarks(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getRemarkByYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getRemarkByYuvak(input);
    // Note: PHP keys this "ddata" (sic) — preserved.
    if (r.length !== 0) responseArr = { error: false, ddata: r };
    else responseArr = { error: true, message: 'Not Data Found.', ddata: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getOnlyKK', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  const r = await new User().getOnlyKK(input.kk_level, input.kk_tid);
  if (r.length !== 0) sendJson(res, { error: false, data: r }, 200);
  else sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
});

router.post('/changeTeam', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['old_kk', 'new_kk']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().changeTeam(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateLongLate', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'longi', 'lat']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateLongLate(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/addSabha', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'title', 'date', 'time', 'isSabha']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().addSabha(input);
    responseArr = ok
      ? { error: false, message: 'Data Added.' }
      : { error: true, message: 'Data Not Added.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateSabha', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['sid', 'title', 'date', 'time', 'isSabha']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateSabha(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/deleteYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'isDelete']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().deleteYuvak(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getSabhaByKK', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getSabhaByKK(input);
    if (r.length !== 0) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.', data: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getAttYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_level', 'tid', 'kk_id', 'sid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getAttYuvak(input);
    const acc = { totalAtt: 0, P: 0, Ab: 0, Un: 0 };
    if (r.length !== 0) {
      for (const v of r) {
        acc.totalAtt += Number(v.total || 0) + Number(v.stotal || 0);
        acc.P += Number(v.P || 0) + Number(v.sP || 0);
        acc.Ab += Number(v.Ab || 0) + Number(v.sAb || 0);
        acc.Un += Number(v.Un || 0) + Number(v.sUn || 0);
      }
      responseArr = { error: false, ...acc, data: r };
    } else {
      responseArr = { error: true, message: 'Not Data Found.', data: [] };
    }
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateAtt', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['sid', 'yid', 'status']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateAtt(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/sendAllNotification', async (req, res) => {
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['title', 'subtitle', 'topic']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().sendAllNotification(firebaseSend, input);
    responseArr = ok
      ? { error: false, message: 'Notification was sent successfully.' }
      : { error: true, message: 'Notification was not sent successfully.' };
  } else {
    responseArr = { error: true, message: 'Missing required parameters.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/sendSabhaNotification', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['title', 'kk_id']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().sendSabhaNotification(firebaseSend, input);
    responseArr = ok
      ? { error: false, message: 'Notification was sent successfully.' }
      : { error: true, message: 'Notification was not sent successfully.' };
  }
  sendJson(res, responseArr, 200);
});

router.get('/sendBdayNotification', async (req, res) => {
  const ok = await new User().sendBdayNotification(firebaseSend);
  sendJson(
    res,
    ok
      ? { error: false, message: 'Notification was sent successfully.' }
      : { error: true, message: 'Notification was not sent successfully.' },
    200
  );
});

router.get('/getRandomYuvak', async (req, res) => {
  await new User().getRandomYuvak(firebaseSend);
  sendJson(res, { error: false, message: 'Notification was sent successfully.' }, 200);
});

router.post('/getAltersByKK', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id']);
  if (Object.keys(responseArr).length === 0) {
    const u = new User();
    const resa = await u.getAllAltersByKK(input);
    const r = await u.getAltersByKK(input);
    if (r.length !== 0) {
      responseArr = { error: false, data: r, alters: resa };
    } else {
      responseArr = { error: true, message: 'Not Data Found.', data: [] };
    }
  }
  sendJson(res, responseArr, 200);
});

router.post('/getUpcomingBday', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'kk_level']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getUpcomingBday(input);
    if (r.length !== 0) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.', data: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/addNewTaskByYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid', 'lastdate', 'title']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().addNewTaskByYuvak(input);
    responseArr = ok
      ? { error: false, message: 'Data Added.' }
      : { error: true, message: 'Data Not Added.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/updateTaskByYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['taskid', 'lastdate', 'title', 'isComp']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().updateTaskByYuvak(input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getAllTaskByYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['yid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getAllTaskByYuvak(input);
    if (r.length !== 0) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.', data: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getAllTaskByKK', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'kk_level', 'tid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getAllTaskByKK(input);
    if (r.length !== 0) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.', data: [] };
  }
  sendJson(res, responseArr, 200);
});

router.post('/yuvakXlReport', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'kk_level']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().yuvakXlReport(input);
    if (r.status === true) responseArr = { error: false, downloadUrl: r.url };
    else responseArr = { error: true, message: 'Not Data Found.', downloadUrl: '' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/insertDbManual', async (req, res) => {
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['statid', 'endid']);
  if (Object.keys(responseArr).length === 0) {
    await new User().insertDbManual(input);
  }
  sendJson(res, responseArr, 200);
});

router.post('/sendBdayNotificationToKK', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['new_kk_id', 'nid', 'yid']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().sendBdayNotificationToKK(firebaseSend, input);
    responseArr = ok
      ? { error: false, message: 'Data Modified.' }
      : { error: true, message: 'Data Not Modified.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/yuvakSabhaReport', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'kk_level', 'sid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().yuvakSabhaReport(input);
    if (r.status === true) responseArr = { error: false, downloadUrl: r.url };
    else responseArr = { error: true, message: 'Not Data Found.', downloadUrl: '' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/insertDefaultMsg', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'type', 'msg']);
  if (Object.keys(responseArr).length === 0) {
    const ok = await new User().insertDefaultMsg(input);
    responseArr = ok
      ? { error: false, message: 'Data Added.' }
      : { error: true, message: 'Data Not Added.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/defaultMsg', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().defaultMsg(input);
    if (r && r.length) responseArr = { error: false, data: r };
    else responseArr = { error: true, message: 'Not Data Found.' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getPresentYuvak', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['sid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().getPresentYuvak(input);
    if (r && r.length) {
      let doneP = 0;
      let cancel = 0;
      for (const i of r) {
        if (i.st === '2') doneP++;
        else if (i.st === '3') cancel++;
      }
      responseArr = {
        error: false,
        total: r.length,
        doneP,
        cancel,
        remaining: r.length - (doneP + cancel),
        data: r,
      };
    } else {
      responseArr = { error: true, message: 'Not Data Found.' };
    }
  }
  sendJson(res, responseArr, 200);
});

router.post('/yuvakXlPadhramniReport', async (req, res) => {
  if (!isJwtValid(req)) return sendJson(res, unauthorized, 401);
  const input = req.body;
  let responseArr = verifyRequiredParams(input, ['kk_id', 'sid']);
  if (Object.keys(responseArr).length === 0) {
    const r = await new User().yuvakXlPadhramniReport(input);
    if (r.status === true) responseArr = { error: false, downloadUrl: r.url };
    else responseArr = { error: true, message: 'Not Data Found.', downloadUrl: '' };
  }
  sendJson(res, responseArr, 200);
});

router.post('/getMentorTeams', async (req, res) => {
  const input = req.body;
  const r = await new User().getMentorTeams(input.kkid);
  if (r.length !== 0) sendJson(res, { error: false, data: r }, 200);
  else sendJson(res, { error: true, message: 'Not Data Found.', data: [] }, 200);
});

module.exports = router;
