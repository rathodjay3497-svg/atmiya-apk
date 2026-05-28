const path = require('path');
const admin = require('firebase-admin');

let initialized = false;
function init() {
  if (initialized) return;
  const saPath = path.resolve(process.env.FIREBASE_SA_PATH);
  const serviceAccount = require(saPath);
  admin.initializeApp({ credential: admin.credential.cert(serviceAccount) });
  initialized = true;
}

async function sendToTopic(topic, title, body) {
  init();
  return admin.messaging().send({
    topic,
    notification: { title, body },
  });
}

module.exports = { sendToTopic };
