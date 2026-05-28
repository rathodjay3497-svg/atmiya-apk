const { Sequelize, QueryTypes } = require('sequelize');

const sequelize = new Sequelize(
  process.env.DB_NAME,
  process.env.DB_USER,
  process.env.DB_PASSWORD,
  {
    host: process.env.DB_HOST,
    dialect: 'mysql',
    dialectOptions: { charset: 'utf8mb4' },
    logging: false,
    pool: { max: 10, min: 0, idle: 10000 },
  }
);

async function selectAll(sql, replacements) {
  return sequelize.query(sql, { replacements, type: QueryTypes.SELECT });
}

async function selectOne(sql, replacements) {
  const rows = await sequelize.query(sql, { replacements, type: QueryTypes.SELECT });
  return rows[0] || null;
}

async function execute(sql, replacements) {
  const [result, meta] = await sequelize.query(sql, { replacements });
  return { result, meta };
}

module.exports = { sequelize, QueryTypes, selectAll, selectOne, execute };
