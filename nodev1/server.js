require('dotenv').config();

const express = require('express');
const cors = require('cors');

const { sequelize } = require('./src/db');
const userRoutes = require('./src/routes/user.routes');
const { setupSwagger } = require('./src/helpers/swagger');
const { sendJson } = require('./src/helpers/response');

const app = express();

app.use(
  cors({
    origin: '*',
    methods: ['GET', 'POST', 'PUT'],
    allowedHeaders: ['X-Requested-With', 'Content-Type', 'Accept', 'Origin', 'Authorization'],
  })
);

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

setupSwagger(app);
app.use(userRoutes);

app.use((err, req, res, next) => {
  // Mirror Slim's JSON error envelope.
  console.error(err);
  sendJson(res, { error: true, message: err.message || 'Internal server error' }, 500);
});

const port = Number(process.env.PORT) || 8080;

app.listen(port, () => {
  console.log(`avdyuvak nodev1 listening on :${port}`);
});

sequelize
  .authenticate()
  .then(() => console.log('DB connected:', process.env.DB_NAME, '@', process.env.DB_HOST))
  .catch((err) => {
    const code = err.original && err.original.code;
    const msg = (err.original && err.original.message) || err.message || String(err);
    console.error(`DB connection failed [${code || 'unknown'}]: ${msg}`);
    console.error('  Server is up but DB-backed routes will fail until DB_HOST/DB_USER/DB_PASSWORD in .env are valid.');
  });
