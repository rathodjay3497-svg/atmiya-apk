const fs = require('fs');
const path = require('path');
const multer = require('multer');

const profilesDir = path.resolve(process.env.PROFILES_DIR || '../profiles');
if (!fs.existsSync(profilesDir)) fs.mkdirSync(profilesDir, { recursive: true });

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, profilesDir),
  filename: (req, file, cb) => {
    // PHP code writes <yid>.jpg. yid is in the form body; multer parses fields
    // before this callback runs.
    const yid = (req.body && (req.body.yid || req.body.YID)) || Date.now();
    cb(null, `${yid}.jpg`);
  },
});

const upload = multer({ storage });

module.exports = { upload, profilesDir };
