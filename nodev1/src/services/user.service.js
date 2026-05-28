// 1:1 port of modelsv1/User.php. SQL strings are preserved verbatim and run
// through Sequelize raw queries with named replacements. The mutable counters
// (regular/irregular/totalAtt/P/Ab/Un) live on a User instance, mirroring the
// PHP class state.

const fs = require('fs');
const path = require('path');
const { sequelize, QueryTypes } = require('../db');

const profilesDir = path.resolve(process.env.PROFILES_DIR || '../profiles');
const REPORT_DIR = path.resolve(__dirname, '../../../report');
const REPORT_BASE_URL = process.env.REPORT_BASE_URL || 'https://paramitsolution.com/avdyuvak/report';

function sel(sql, replacements) {
  return sequelize.query(sql, { replacements, type: QueryTypes.SELECT });
}
function one(sql, replacements) {
  return sel(sql, replacements).then((rs) => rs[0] || null);
}
function exec(sql, replacements) {
  return sequelize.query(sql, { replacements });
}

class User {
  constructor() {
    this.regular = 0;
    this.irregular = 0;
    this.totalAtt = 0;
    this.P = 0;
    this.Ab = 0;
    this.Un = 0;
    this.level_1id = 1;
  }

  async checkLogin(mobile, password) {
    try {
      const row = await one(
        'SELECT * FROM yuvak where mobile=:mobile AND password=:password AND isLogin=:isLogin',
        { mobile, password, isLogin: 1 }
      );
      const result = row ? { ...row } : {};
      result.error = !row;
      if (row) {
        const mentors = await sel('SELECT * FROM `mentor` WHERE `main_kk_id` = :main_kk_id', {
          main_kk_id: row.yid,
        });
        result.isMentor = mentors.length > 0 ? '1' : '0';
      }
      return result;
    } catch (e) {
      return { error: true };
    }
  }

  async changePassword({ yid, password }) {
    try {
      await exec('UPDATE yuvak SET password=:password where yid=:yid', { password, yid });
      return true;
    } catch (e) {
      return false;
    }
  }

  async insertDbManual({ statid, endid }) {
    // PHP body is mostly commented out; preserves the same no-op behaviour.
    return true;
  }

  async updateLogin({ yid, status }) {
    try {
      await exec('UPDATE yuvak SET isLogin=:isLogin where yid=:yid', { isLogin: status, yid });
      return true;
    } catch (e) {
      return false;
    }
  }

  async getConditons() {
    try {
      return await one('select * from conditions', {});
    } catch (e) {
      return null;
    }
  }

  async updateLongLate({ longi, lat, yid }) {
    try {
      await exec('UPDATE yuvak SET longi=:longi, lat=:lat where yid=:yid', { longi, lat, yid });
      return true;
    } catch (e) {
      return false;
    }
  }

  async getMinSabha(totalSabha) {
    const row = await this.getConditons();
    return Math.round((row.minSabha / row.maxSabha) * totalSabha);
  }

  async getSabhaByLevel1(kk_id) {
    const rows = await sel('SELECT * FROM sabha s where s.kk_id=:kk_id AND isSabha=1', { kk_id });
    return this._buildSabhaCol(rows);
  }

  async getPadhramniByLevel(kk_id) {
    const rows = await sel('SELECT * FROM sabha s where s.kk_id=:kk_id AND isSabha=2', { kk_id });
    return this._buildSabhaCol(rows);
  }

  _buildSabhaCol(rows) {
    const colA = [];
    let columnName = '(';
    rows.forEach((v, k) => {
      const piece = `COUNT(CASE WHEN s${v.sid}=1 THEN 1 END)`;
      columnName += k === rows.length - 1 ? piece : piece + '+';
      colA.push('s' + v.sid);
    });
    columnName += ') as total';
    return { col: columnName, totalSabha: rows.length, colA };
  }

  async getYuvakStatus(column, yid) {
    const ycdt = await one('SELECT cdt FROM yuvak where yid=' + Number(yid), {});
    const yuvakTotalSabha = await one(
      'SELECT COUNT(*) AS yuvakTotalSabha FROM sabha WHERE kk_id = 1 AND date >= DATE(:date)',
      { date: ycdt ? ycdt.cdt : null }
    );
    const row = await one(`SELECT ${column.col} FROM attendance where yid=` + Number(yid), {});
    return {
      st: row ? row.total : 0,
      yuvakTotalSabha: yuvakTotalSabha ? yuvakTotalSabha.yuvakTotalSabha : 0,
    };
  }

  _classifySabha(row, column, sa) {
    if (sa.yuvakTotalSabha <= 8) return '1';
    if (column.totalSabha >= 9 && sa.st >= 4) return '2';
    if (column.totalSabha >= 9 && sa.st == 0) return '4';
    if (column.totalSabha >= 9 && sa.st < 3) return '3';
    return row.sabhaSta;
  }

  async getYuvakByTeam(input) {
    try {
      const column = await this.getSabhaByLevel1(this.level_1id);
      column.totalSabha = await this.getMinSabha(column.totalSabha);

      let rows;
      const next = Number(input.kk_level) + 1;
      if (Number(input.kk_level) === 1) {
        rows = await sel(
          'SELECT * FROM yuvak y,teams t,address a where isDeleted=0 AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND a.aid = y.aid order by t.tname',
          { kk_level: next, kk_id: input.kk_id }
        );
      } else {
        rows = await sel(
          'SELECT * FROM yuvak y,teams t,address a where isDeleted=0 AND  y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND y.tid=:tid AND a.aid = y.aid  order by y.name',
          { kk_level: next, kk_id: input.kk_id, tid: input.tid }
        );
      }

      for (const value of rows) {
        const sa = await this.getYuvakStatus(column, value.yid);
        value.ssssssssssssssssssssssss = sa;
        value.sabhaSta = Number(input.kk_level) === 1 ? undefined : '1';
        const cls = this._classifySabha(value, column, sa);
        if (cls !== undefined) value.sabhaSta = cls;

        const r = await one(
          'SELECT count(*) c FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.tid=:tid',
          { kk_level: value.kk_level, tid: value.tid }
        );
        value.totalSubYuvak = r ? r.c : 0;

        value.totalSubYuvak = await this.getSubYuvakSabhaStatus(
          value.yid,
          value.kk_level,
          value.tid,
          column
        );
        value.subRegular = this.regular;
        value.subIrregular = this.irregular;
        this.regular = 0;
        this.irregular = 0;
      }
      return rows;
    } catch (e) {
      this.regular = 0;
      this.irregular = 0;
      return [];
    }
  }

  async getYuvakByCategory(input) {
    try {
      const column = await this.getSabhaByLevel1(this.level_1id);
      column.totalSabha = await this.getMinSabha(column.totalSabha);

      const next = Number(input.kk_level) + 1;
      let rows;
      if (next <= 3) {
        rows = await sel(
          'SELECT * FROM yuvak y,teams t,address a where y.tid = t.tid AND  y.kk_level=:kk_level  AND a.aid = y.aid AND y.type =:type  order by y.name',
          { kk_level: next, type: input.type }
        );
      } else {
        rows = await sel(
          'SELECT * FROM yuvak y,teams t,address a where y.tid = t.tid AND  y.kk_level=:kk_level  AND y.kk_id=:kk_id AND a.aid = y.aid AND y.type =:type   order by y.name',
          { kk_level: next, kk_id: input.kk_id, type: input.type }
        );
      }

      for (const value of rows) {
        const sa = await this.getYuvakStatus(column, value.yid);
        value.sabhaSta = '1';
        const cls = this._classifySabha(value, column, sa);
        if (cls !== undefined) value.sabhaSta = cls;

        const r = await one(
          'SELECT count(*) c FROM yuvak y where y.kk_level>:kk_level  AND y.type =:type',
          { kk_level: value.kk_level, type: input.type }
        );
        value.totalSubYuvak = r ? r.c : 0;

        value.totalSubYuvak = await this.getSubYuvakSabhaStatusByCategory(
          value.yid,
          value.kk_level,
          value.tid,
          column
        );
        value.subRegular = this.regular;
        value.subIrregular = this.irregular;
        this.regular = 0;
        this.irregular = 0;
      }
      return rows;
    } catch (e) {
      this.regular = 0;
      this.irregular = 0;
      return [];
    }
  }

  async getSearchYuvak(input) {
    const like = String(input.query || '');
    const safe = like.replace(/[\\%_']/g, (m) => '\\' + m);
    const wrap = `%${safe}%`;
    if (Number(input.kk_level) === 1 && input.type === 'name') {
      return sel(
        "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.name Like :wrap order by t.tname",
        { kk_level: input.kk_level, wrap }
      );
    }
    if (Number(input.kk_level) === 1 && input.type === 'mobile') {
      return sel(
        "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND  y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.mobile Like :wrap order by t.tname",
        { kk_level: input.kk_level, wrap }
      );
    }
    if (input.type === 'name') {
      return sel(
        "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.tid=:tid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.name Like :wrap order by t.tname",
        { kk_level: input.kk_level, tid: input.tid, wrap }
      );
    }
    if (input.type === 'mobile') {
      return sel(
        "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.tid=:tid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.mobile Like :wrap order by t.tname",
        { kk_level: input.kk_level, tid: input.tid, wrap }
      );
    }
    return [];
  }

  async getSubYuvakSabhaStatus(kk_id, kk_level, tid, column) {
    const r = await sel(
      'SELECT * FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.tid=:tid AND y.kk_id=:kk_id',
      { kk_id, kk_level, tid }
    );
    let yc = r.length;
    for (const val of r) {
      if (r.length !== 0) {
        if (column.totalSabha !== 0) {
          const sa = await this.getYuvakStatus(column, val.yid);
          if (sa.st == 1) this.regular += 1;
          else if (sa.st == 0) this.irregular += 1;
        } else {
          this.irregular += 1;
        }
        yc += await this.getSubYuvakSabhaStatus(val.yid, val.kk_level, val.tid, column);
      } else {
        return yc;
      }
    }
    return yc;
  }

  async getSubYuvakSabhaStatusByCategory(kk_id, kk_level, tid, column) {
    const r = await sel(
      "SELECT * FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.kk_id = :kk_id AND (y.type IN ('Kishor Mandal'))",
      { kk_id, kk_level }
    );
    let yc = r.length;
    for (const val of r) {
      if (r.length !== 0) {
        if (column.totalSabha !== 0) {
          const sa = await this.getYuvakStatus(column, val.yid);
          if (sa.st == 1) this.regular += 1;
          else if (sa.st == 0) this.irregular += 1;
        } else {
          this.irregular += 1;
        }
        yc += await this.getSubYuvakSabhaStatusByCategory(val.yid, val.kk_level, val.tid, column);
      } else {
        return yc;
      }
    }
    return yc;
  }

  async getEduOptions() {
    try {
      const mainRows = await sel('SELECT * FROM education WHERE isshow=1 AND pid = 0', {});
      for (const val of mainRows) {
        val.subData = await sel('SELECT * FROM education WHERE isshow = 1 AND pid = :pid', {
          pid: val.eid,
        });
      }
      return mainRows;
    } catch (e) {
      return [];
    }
  }

  async getYuvakCategoryOptions() {
    try {
      const r = await one(
        "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'yuvak' AND COLUMN_NAME = 'type'",
        {}
      );
      const m = /^enum\((.*)\)$/.exec(r.COLUMN_TYPE);
      if (!m) return [];
      return m[1].split(',').map((s) => s.replace(/'/g, ''));
    } catch (e) {
      return [];
    }
  }

  async getMentorTeams(kkid) {
    try {
      return await sel(
        'SELECT mentor.*,t.tname,t.tid FROM `mentor`,yuvak y,teams t WHERE y.tid = t.tid AND sub_kk_id = y.yid AND main_kk_id = :kkid',
        { kkid }
      );
    } catch (e) {
      return [];
    }
  }

  async getTeamsOption() {
    try {
      return await sel(
        'SELECT * FROM teams WHERE  NOT tid IN (SELECT DISTINCT(tid) FROM yuvak) ORDER BY tname',
        {}
      );
    } catch (e) {
      return [];
    }
  }

  async addYuvak(yuvak, file) {
    try {
      const exists = await sel('SELECT * FROM yuvak where mobile=:mobile', { mobile: yuvak.mobile });
      if (exists.length !== 0) return false;

      const [addrResult] = await sequelize.query(
        'INSERT INTO address SET address=:address, city=:city, state=:state, pincode=:pincode, country=:country',
        {
          replacements: {
            address: yuvak.address,
            city: yuvak.city,
            state: yuvak.state,
            pincode: yuvak.pincode,
            country: yuvak.country,
          },
        }
      );
      const aid = addrResult;

      const [yidResult] = await sequelize.query(
        'INSERT INTO yuvak SET name=:name, fname=:fname, surname=:surname, dob=:dob, mobile=:mobile, tid=:tid, kk_level=:kk_level, eid=:eid,seid=:seid, kk_id=:kk_id,aid=:aid,edesc=:edesc,eyear=:eyear,emark=:emark,type=:yuvakType',
        {
          replacements: {
            name: yuvak.name,
            fname: yuvak.fname,
            surname: yuvak.surname,
            dob: yuvak.dob,
            mobile: yuvak.mobile,
            kk_id: yuvak.kk_id,
            tid: yuvak.tid,
            kk_level: yuvak.kk_level,
            eid: yuvak.eid,
            seid: yuvak.seid,
            aid,
            emark: yuvak.emark,
            eyear: yuvak.eyear,
            edesc: yuvak.edesc,
            yuvakType: yuvak.yuvakType,
          },
        }
      );
      const yid = yidResult;

      await exec('INSERT INTO attendance SET yid=:yid', { yid });
      await exec('INSERT INTO contact SET yid=:yid', { yid });

      const kkId = 'k' + yuvak.kk_id;
      const cols = await sel('SHOW COLUMNS FROM contact WHERE Field =:Field', { Field: kkId });
      if (cols.length === 0) {
        await exec(`ALTER TABLE contact ADD ${kkId} INT(1) DEFAULT 0 after yid`, {});
      }

      if (yuvak.isImage === 'true' && file) {
        const ext = path.extname(file.originalname || '.jpg').slice(1) || 'jpg';
        const imgName = `${yid}.${ext}`;
        const target = path.join(profilesDir, imgName);
        fs.renameSync(file.path, target);
        await exec('UPDATE yuvak SET img=:img where yid=:yid', { img: imgName, yid });
        return true;
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  async updateYuvak(yuvak, file) {
    try {
      await exec(
        'UPDATE  address SET address=:address, city=:city, state=:state, pincode=:pincode, country=:country where aid=:aid',
        {
          address: yuvak.address,
          city: yuvak.city,
          state: yuvak.state,
          pincode: yuvak.pincode,
          country: yuvak.country,
          aid: yuvak.aid,
        }
      );
      await exec(
        'UPDATE  yuvak SET name=:name, fname=:fname, surname=:surname, dob=:dob, eid=:eid,seid=:seid,edesc=:edesc,eyear=:eyear,emark=:emark,type=:yuvakType where yid=:yid',
        {
          name: yuvak.name,
          fname: yuvak.fname,
          surname: yuvak.surname,
          dob: yuvak.dob,
          eid: yuvak.eid,
          seid: yuvak.seid,
          yid: yuvak.yid,
          emark: yuvak.emark,
          eyear: yuvak.eyear,
          edesc: yuvak.edesc,
          yuvakType: yuvak.yuvakType,
        }
      );
      const yid = yuvak.yid;

      if (yuvak.isImage === 'true' && file) {
        const ext = path.extname(file.originalname || '.jpg').slice(1) || 'jpg';
        const imgName = `${yid}.${ext}`;
        const target = path.join(profilesDir, imgName);
        fs.renameSync(file.path, target);
        await exec('UPDATE yuvak SET img=:img where yid=:yid', { img: imgName, yid });
        return true;
      }

      if (yuvak.newMobileNumber !== yuvak.mobile) {
        const dup = await sel('SELECT * FROM yuvak where mobile=:mobile', { mobile: yuvak.mobile });
        if (dup.length === 0) {
          await exec('UPDATE  yuvak SET  mobile=:mobile where yid=:yid', {
            mobile: yuvak.mobile,
            yid: yuvak.yid,
          });
          return true;
        }
        return 'mobile';
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  async addRemarks(remark) {
    try {
      if (Number(remark.rel) === 2) {
        const r = await one(
          'SELECT COUNT(*) as c FROM remarks WHERE yid = :yid AND sid = :sid',
          { yid: remark.yid, sid: remark.sid }
        );
        if (r && r.c > 0) {
          await exec(
            'UPDATE remarks SET remark = :remark, rel = :rel WHERE yid = :yid AND sid = :sid',
            { remark: remark.remark, rel: remark.rel, yid: remark.yid, sid: remark.sid }
          );
        } else {
          await exec(
            'INSERT INTO remarks (yid, remark, rel, sid) VALUES (:yid, :remark, :rel, :sid)',
            { remark: remark.remark, rel: remark.rel, yid: remark.yid, sid: remark.sid }
          );
        }
      } else if (Number(remark.rel) === 3) {
        await exec(
          'INSERT INTO remarks SET yid=:yid, remark=:remark, rel=:rel,taskid=:taskid',
          {
            yid: remark.yid,
            remark: remark.remark,
            rel: remark.rel,
            taskid: remark.taskid,
          }
        );
      } else {
        await exec('INSERT INTO remarks SET yid=:yid, remark=:remark, rel=:rel', {
          yid: remark.yid,
          remark: remark.remark,
          rel: remark.rel,
        });
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  async updateRemarks(remark) {
    try {
      await exec('UPDATE  remarks SET remark=:remark, rel=:rel where  rid=:rid', {
        rid: remark.rid,
        remark: remark.remark && String(remark.remark).length > 0 ? remark.remark : '',
        rel: remark.rel,
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  async getRemarkByYuvak(remark) {
    try {
      return await sel('SELECT * FROM remarks where yid=:yid ORDER BY cdt DESC', { yid: remark.yid });
    } catch (e) {
      return [];
    }
  }

  async getOnlyKK(kk_level, kktid) {
    const login = '1';
    try {
      if (String(kk_level) === '1') {
        return await sel(
          'SELECT * FROM yuvak y,teams t WHERE y.isLogin=:isLogin AND  t.tid=y.tid  ORDER BY y.name',
          { isLogin: login }
        );
      }
      return await sel(
        'SELECT * FROM yuvak y,teams t WHERE y.isLogin=:isLogin AND  t.tid=y.tid AND y.kk_level >= 3 ANd y.tid = :kktid ORDER BY y.name',
        { isLogin: login, kktid }
      );
    } catch (e) {
      return [];
    }
  }

  async changeTeam({ old_kk, new_kk }) {
    try {
      await this._changeTeamData(old_kk, new_kk);
      return true;
    } catch (e) {
      return false;
    }
  }

  async _changeTeamData(kk_id, new_kk_id) {
    const newkk = await one('SELECT * FROM yuvak where yid=:yid', { yid: new_kk_id });
    const newTid = newkk.tid;
    const newKKId = newkk.yid;
    const new_kk_level = Number(newkk.kk_level) + 1;

    await exec(
      'UPDATE yuvak SET tid=:tid, kk_level=:kk_level, kk_id=:kk_id where yid=:yid',
      { tid: newTid, kk_level: new_kk_level, kk_id: newKKId, yid: kk_id }
    );

    const subs = await sel('SELECT * FROM yuvak where kk_id=:kk_id', { kk_id });
    for (const subkk of subs) {
      await this._changeTeamData(subkk.yid, kk_id);
    }
  }

  async addSabha(input) {
    try {
      const [sid] = await sequelize.query(
        'INSERT INTO sabha SET kk_id=:kk_id, title=:title, date=:date,time=:time,isSabha=:isSabha',
        {
          replacements: {
            kk_id: input.kk_id,
            title: input.title,
            date: input.date,
            time: input.time,
            isSabha: input.isSabha,
          },
        }
      );
      const columnName = 's' + sid;
      await exec(`ALTER TABLE attendance ADD ${columnName} INT(1) DEFAULT 0 after yid`, {});
      return true;
    } catch (e) {
      return false;
    }
  }

  async sendSabhaNotification(firebaseSend, input) {
    const kkDetails = await one('SELECT * FROM yuvak where yid=:yid', { yid: input.kk_id });
    if (kkDetails && Number(kkDetails.kk_level) === 1) {
      const subkkDetails = await sel('SELECT * FROM yuvak where isLogin=1', {});
      const title = `${input.title},The new sabha was created by ${kkDetails.name} ${kkDetails.surname}.`;
      const subtitle = 'Please fill the Attdence.';
      for (const value of subkkDetails) {
        await firebaseSend({ topic: String(value.yid), title, subtitle });
      }
      return true;
    }
    return true;
  }

  async updateSabha(input) {
    try {
      await exec(
        'UPDATE sabha SET title=:title, date=:date,time=:time,isSabha=:isSabha where sid=:sid',
        {
          sid: input.sid,
          title: input.title,
          date: input.date,
          time: input.time,
          isSabha: input.isSabha,
        }
      );
      return true;
    } catch (e) {
      return false;
    }
  }

  async deleteYuvak(input) {
    try {
      await exec('UPDATE yuvak SET isDeleted=:isDelete where yid=:yid', {
        yid: input.yid,
        isDelete: input.isDelete,
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  async getSabhaByKK(input) {
    try {
      const kkDetails = await one('SELECT * FROM yuvak where yid=:yid', { yid: input.kk_id });
      if (!kkDetails) return [];
      let rows;
      if (Number(kkDetails.kk_level) === 1) {
        rows = await sel(
          'SELECT * FROM sabha where isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak where  (isLogin=1 AND kk_level<:kk_level ) OR yid=:yid) order by cdt desc',
          { kk_level: kkDetails.kk_level, yid: input.kk_id }
        );
      } else {
        rows = await sel(
          'SELECT * FROM sabha where isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak where isLogin=1 AND ((tid=:tid AND kk_level<=:kk_level) OR (kk_level<=:kk_level AND kk_level=1))) order by cdt desc',
          { kk_level: kkDetails.kk_level, tid: kkDetails.tid }
        );
      }
      for (const value of rows) {
        const cname = value.sid;
        const r = await one(
          `SELECT count(*) as t, COUNT(CASE WHEN s${cname}=1 THEN 1 END) as p,COUNT(CASE WHEN s${cname}=0 THEN 1 END) as un,COUNT(CASE WHEN s${cname}=2 THEN 1 END) as ab FROM attendance`,
          {}
        );
        value.totalYuvak = r.t;
        value.present = r.p;
        value.absent = r.ab;
        value.undefine = r.un;
      }
      return rows;
    } catch (e) {
      return [];
    }
  }

  async getAttYuvak(input) {
    try {
      let rows;
      const next = Number(input.kk_level) + 1;
      if (Number(input.kk_level) === 1) {
        rows = await sel(
          `SELECT y.*,t.*,at.s${input.sid} status FROM yuvak y,teams t,address a,attendance at where y.yid=at.yid AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND a.aid = y.aid order by y.name`,
          { kk_level: next, kk_id: input.kk_id }
        );
      } else {
        rows = await sel(
          `SELECT y.*,t.*,at.s${input.sid} status  FROM yuvak y,teams t,address a,attendance at where   y.yid=at.yid AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND y.tid=:tid AND a.aid = y.aid order by y.name`,
          { kk_level: next, kk_id: input.kk_id, tid: input.tid }
        );
      }

      for (const r of rows) {
        r.status = String(r.status == null ? '' : r.status);
        const re = await one('SELECT * FROM remarks where yid=:yid AND sid=:sid', {
          yid: r.yid,
          sid: input.sid,
        });
        if (re) r.remark = re.remark;
      }

      const sid = input.sid;
      for (const value of rows) {
        const r = await one(
          `SELECT count(*) as t, COUNT(CASE WHEN s${sid}=1 THEN 1 END) as p,COUNT(CASE WHEN s${sid}=0 THEN 1 END) as un,COUNT(CASE WHEN s${sid}=2 THEN 1 END) as ab FROM attendance where yid=:yid`,
          { yid: value.yid }
        );
        value.stotal = String(r.t);
        value.sP = String(r.p);
        value.sAb = String(r.ab);
        value.sUn = String(r.un);

        await this._findSubYuvak(value.yid, sid);
        value.total = this.totalAtt;
        value.P = this.P;
        value.Ab = this.Ab;
        value.Un = this.Un;
        this.totalAtt = 0;
        this.P = 0;
        this.Ab = 0;
        this.Un = 0;
      }
      return rows;
    } catch (e) {
      return [];
    }
  }

  async _findSubYuvak(kkId, sid) {
    const rows = await sel('SELECT * FROM yuvak where kk_id=:kk_id', { kk_id: kkId });
    if (rows.length !== 0) {
      for (const value of rows) {
        const r = await one(
          `SELECT count(*) as t, COUNT(CASE WHEN s${sid}=1 THEN 1 END) as p,COUNT(CASE WHEN s${sid}=0 THEN 1 END) as un,COUNT(CASE WHEN s${sid}=2 THEN 1 END) as ab FROM attendance where yid=:yid`,
          { yid: value.yid }
        );
        this.totalAtt += Number(r.t || 0);
        this.P += Number(r.p || 0);
        this.Ab += Number(r.ab || 0);
        this.Un += Number(r.un || 0);
        await this._findSubYuvak(value.yid, sid);
      }
    }
  }

  async updateAtt(input) {
    const sid = 's' + input.sid;
    try {
      await exec(`UPDATE  attendance SET ${sid}=:status where  yid=:yid`, {
        yid: input.yid,
        status: input.status,
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  async sendAllNotification(firebaseSend, input) {
    try {
      await firebaseSend({
        topic: String(input.topic),
        title: String(input.title),
        subtitle: String(input.subtitle),
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  async sendBdayNotification(firebaseSend) {
    try {
      const date = new Date().toISOString().slice(0, 10);
      const yuvaks = await sel(
        "SELECT y.* FROM yuvak y,teams t where t.tid=y.tid AND  DATE_FORMAT(DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),'-', MONTH(dob),'-', DAY(dob))),'%Y-%m-%d')=:date",
        { date }
      );

      for (const value of yuvaks) {
        const kk = await one('SELECT name,surname,kk_level FROM yuvak where yid=:yid', {
          yid: value.kk_id,
        });
        const name = `${value.name} ${value.surname}`;
        const kkname = kk ? `${kk.name} ${kk.surname}` : '';
        const tname = value.tname || '';
        const input = {
          title: name,
          subtitle: `Birthday : ${value.dob}\n${kkname}\n${tname}`,
          topic: String(value.kk_id),
        };
        const isForward = kk && Number(kk.kk_level) === 1 ? 1 : 0;
        await exec(
          'INSERT INTO notification SET yid=:yid, kk_id=:kk_id, isForward=:isForward',
          { yid: value.yid, kk_id: value.kk_id, isForward }
        );
        await this.sendAllNotification(firebaseSend, input);
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  async getRandomYuvak(firebaseSend) {
    const maxYuvak = 3;
    try {
      const cols = await sel('SHOW COLUMNS FROM contact', {});
      let lastRow;
      for (const value of cols) {
        if (value.Field === 'yid' || value.Field === 'cid') continue;
        const kkId = value.Field.replace(/^k/, '');
        const kkDetails = await one('SELECT * FROM yuvak where yid=:yid', { yid: kkId });
        if (!kkDetails) continue;

        let row;
        if (Number(kkDetails.kk_level) === 1) {
          row = await sel(
            `SELECT *  FROM contact c,yuvak y where  c.${value.Field}=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level) AND c.yid=y.yid) Order by y.name`,
            { kk_level: kkDetails.kk_level }
          );
        } else {
          row = await sel(
            `SELECT *  FROM contact c,yuvak y where  c.${value.Field}=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level AND tid=:tid AND kk_id=:kkid) AND c.yid=y.yid)  Order by y.name`,
            { kk_level: kkDetails.kk_level, kkid: kkDetails.yid, tid: kkDetails.tid }
          );
        }

        const notiYuvak = await this._insertNoti(3, kkId);

        if (row.length < 3) {
          const j = await this._sendRandomYuvakNoti(
            firebaseSend,
            row.length,
            value,
            row,
            notiYuvak,
            kkId,
            0
          );
          const newCount = maxYuvak - row.length;
          await exec(`UPDATE contact SET ${value.Field}=0`, {});

          if (Number(kkDetails.kk_level) === 1) {
            row = await sel(
              `SELECT *  FROM contact c,yuvak y where  c.${value.Field}=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level) AND c.yid=y.yid) Order by y.name`,
              { kk_level: kkDetails.kk_level }
            );
          } else {
            row = await sel(
              `SELECT * FROM contact c,yuvak y where c.${value.Field}=0 AND (c.yid IN (SELECT yid FROM yuvak where kk_level>:kk_level AND tid=:tid ) AND c.yid=y.yid) Order by y.name`,
              { kk_level: kkDetails.kk_level, tid: kkDetails.tid }
            );
          }
          await this._sendRandomYuvakNoti(firebaseSend, newCount, value, row, notiYuvak, kkId, j);
        } else {
          await this._sendRandomYuvakNoti(firebaseSend, maxYuvak, value, row, notiYuvak, kkId, 0);
        }
        lastRow = row;
      }
      return lastRow || [];
    } catch (e) {
      return [];
    }
  }

  async _sendRandomYuvakNoti(firebaseSend, count, value, row, notiYuvak, kkId, temp) {
    let j = 0;
    for (let i = 0; i < count; i++) {
      await exec(`UPDATE contact SET ${value.Field}=1 where  cid=:cid`, { cid: row[i].cid });
      await exec('UPDATE notification SET yid=:yid where nid=:nid', {
        yid: row[i].yid,
        nid: notiYuvak[i + temp].nid,
      });
      const yuvakKK = await one(
        'Select * from yuvak y,teams t where y.yid=:kk_id AND y.tid=t.tid',
        { kk_id: row[i].kk_id }
      );
      const name = `${row[i].name} ${row[i].surname}`;
      const kkname = yuvakKK ? `${yuvakKK.name} ${yuvakKK.surname}` : '';
      const tname = yuvakKK ? yuvakKK.tname : '';
      await this.sendAllNotification(firebaseSend, {
        title: name,
        subtitle: `${kkname}\n${tname}`,
        topic: String(kkId),
      });
      j++;
    }
    return j;
  }

  async _insertNoti(count, kkId) {
    let row = await sel(
      'Select * from notification where kk_id=:kk_id AND isRandom=1',
      { kk_id: kkId }
    );
    if (row.length >= count) return row;
    for (let i = 0; i < count - row.length; i++) {
      await exec(
        'INSERT INTO notification SET yid=0,kk_id=:kk_id,isRandom=1',
        { kk_id: kkId }
      );
    }
    return sel('Select * from notification where kk_id=:kk_id AND isRandom=1', { kk_id: kkId });
  }

  async getAltersByKK(input) {
    try {
      const yuvakDetails = await sel(
        'SELECT * FROM notification n,yuvak y,teams t where y.tid = t.tid AND n.kk_id=:kk_id AND n.isRandom=1 AND n.yid=y.yid Order by y.name',
        { kk_id: input.kk_id }
      );
      for (const value of yuvakDetails) {
        value.kk = await one('SELECT * FROM yuvak y where yid=:yid', { yid: value.kk_id });
      }
      return yuvakDetails;
    } catch (e) {
      return [];
    }
  }

  async getAllAltersByKK(input) {
    try {
      return await sel(
        'SELECT * FROM notification n,yuvak y,teams t where y.tid = t.tid AND n.kk_id=:kk_id  AND n.yid=y.yid AND isRandom=0 AND (MONTH(y.dob)<MONTH(CURRENT_TIMESTAMP)  OR (DAY(y.dob)<=DAY(CURRENT_TIMESTAMP) AND MONTH(y.dob)=MONTH(CURRENT_TIMESTAMP))) Order by n.cdt desc limit 10',
        { kk_id: input.kk_id }
      );
    } catch (e) {
      return [];
    }
  }

  async getUpcomingBday(input) {
    try {
      const kkLevel = Number(input.kk_level);
      const bdayCols = `
        CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
              YEAR(CURRENT_TIMESTAMP)
            ELSE
              YEAR(CURRENT_TIMESTAMP)+1
            END,'-', MONTH(dob),'-', DAY(dob)) as newDate,
        DATE_FORMAT(DATE(CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
              YEAR(CURRENT_TIMESTAMP)
            ELSE
              YEAR(CURRENT_TIMESTAMP)+1
            END,'-', MONTH(dob),'-', DAY(dob))),'%d-%m-%Y') as newDob`;
      let rows;
      if (kkLevel === 1) {
        rows = await sel(
          `SELECT y.*,t.*, ${bdayCols} FROM yuvak y,teams t where y.tid = t.tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10`,
          { kk_level: kkLevel + 1 }
        );
      } else if (kkLevel === 2) {
        rows = await sel(
          `SELECT y.*,t.*, ${bdayCols} FROM yuvak y,teams t where y.tid = t.tid  AND y.tid =:tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10`,
          { kk_level: kkLevel + 1, tid: input.tid }
        );
      } else if (kkLevel === 3) {
        rows = await sel(
          `SELECT y.*,t.*, ${bdayCols} FROM yuvak y,teams t where y.tid = t.tid AND y.kk_id=:kk_id AND y.tid =:tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10`,
          { kk_level: kkLevel + 1, kk_id: input.kk_id, tid: input.tid }
        );
      } else {
        rows = [];
      }
      for (const value of rows) {
        value.kk = await one('SELECT * FROM yuvak y where y.yid=:yid', { yid: value.kk_id });
      }
      return rows;
    } catch (e) {
      return [];
    }
  }

  async addNewTaskByYuvak(task) {
    try {
      await exec('INSERT INTO task SET yid=:yid, lastdate=:lastdate, taskname=:title', {
        yid: task.yid,
        lastdate: task.lastdate,
        title: task.title,
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  async updateTaskByYuvak(task) {
    try {
      await exec(
        'UPDATE task SET isCompleted=:isCompleted, lastdate=:lastdate, taskname=:taskname where taskid=:taskid',
        {
          taskid: task.taskid,
          lastdate: task.lastdate,
          taskname: task.title,
          isCompleted: task.isComp,
        }
      );
      return true;
    } catch (e) {
      return false;
    }
  }

  async getAllTaskByYuvak(input) {
    try {
      const tasks = await sel('SELECT * FROM task where yid=:yid order by cdt desc', {
        yid: input.yid,
      });
      for (const value of tasks) {
        value.taskRemark = await sel(
          'SELECT * FROM remarks where taskid=:taskid order by cdt desc',
          { taskid: value.taskid }
        );
      }
      return tasks;
    } catch (e) {
      return [];
    }
  }

  async getAllTaskByKK(input) {
    try {
      const kkLevel = Number(input.kk_level) + 1;
      let yuvaks;
      if (Number(input.kk_level) === 1) {
        yuvaks = await sel(
          'SELECT * FROM yuvak y,teams t where y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname',
          { kk_level: kkLevel }
        );
      } else {
        yuvaks = await sel(
          'SELECT * FROM yuvak y,teams t where y.tid = t.tid  AND y.kk_level >=:kk_level AND t.tid=:tid order by y.name',
          { kk_level: kkLevel, tid: input.tid }
        );
      }

      const newArray = [];
      for (const val of yuvaks) {
        const tasks = await sel('SELECT * FROM task where yid=:yid order by cdt desc', {
          yid: val.yid,
        });
        for (const t of tasks) newArray.push(t);
      }
      for (const value of newArray) {
        value.kkRemarks = await sel(
          'SELECT * FROM remarks where taskid=:taskid order by cdt desc',
          { taskid: value.taskid }
        );
      }
      for (const value of newArray) {
        value.subYuvak = await one(
          'SELECT * FROM yuvak y,teams t where y.yid=:yid AND t.tid=y.tid',
          { yid: value.yid }
        );
      }
      return newArray;
    } catch (e) {
      return [];
    }
  }

  async insertDefaultMsg(input) {
    try {
      const rows = await sel('SELECT * from msg where kk_id=:kk_id', { kk_id: input.kk_id });
      if (rows.length === 0) {
        await exec('INSERT INTO msg SET kk_id=:kk_id,message=:msg,type=:type', {
          kk_id: input.kk_id,
          msg: input.msg,
          type: input.type,
        });
      } else {
        await exec('Update msg SET message=:msg,type=:type where kk_id=:kk_id', {
          kk_id: input.kk_id,
          msg: input.msg,
          type: input.type,
        });
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  async defaultMsg(input) {
    try {
      const rows = await sel('SELECT * from msg where kk_id=:kk_id', { kk_id: input.kk_id });
      if (rows.length === 0) return [];
      rows[0].mid = String(rows[0].mid);
      rows[0].kk_id = String(rows[0].kk_id);
      rows[0].type = String(rows[0].type);
      return rows;
    } catch (e) {
      return [];
    }
  }

  async getPresentYuvak(input) {
    const sid = input.sid;
    try {
      const rows = await sel(
        `SELECT y.*,kk.name as kname,kk.fname as kfname,kk.surname as ksname,t.tname, a.s${sid} as st  FROM teams t, yuvak kk,yuvak y,attendance a where t.tid=y.tid AND kk.yid = y.kk_id AND y.yid=a.yid AND a.s${sid} IN (1,2,3) order by t.tname`,
        {}
      );
      const stringify = ['yid', 'aid', 'fid', 'mid', 'tid', 'kk_level', 'kk_id', 'isLogin', 'eid', 'isDeleted', 'st'];
      for (const r of rows) {
        for (const v of stringify) r[v] = String(r[v] == null ? '' : r[v]);
      }
      return rows;
    } catch (e) {
      return [];
    }
  }

  async sendBdayNotificationToKK(firebaseSend, input) {
    const { new_kk_id: kkid, nid, yid } = input;
    const yuvak = await one('SELECT name,surname,dob FROM yuvak where yid=:yid', { yid });
    const kk = await one(
      'SELECT y.name,y.surname,t.tname,y.kk_level FROM yuvak y,teams t where yid=:yid AND y.tid=t.tid',
      { yid: kkid }
    );
    if (!yuvak || !kk) return false;
    const name = `${yuvak.name} ${yuvak.surname}`;
    const kkname = `${kk.name} ${kk.surname}`;
    const subtitle = `Birthday : ${yuvak.dob}\n${kkname}\n${kk.tname}`;

    await exec('INSERT INTO notification SET yid=:yid, kk_id=:kk_id, isForward=:isForward', {
      yid,
      kk_id: kkid,
      isForward: Number(kk.kk_level) === 1 ? 1 : 0,
    });
    await exec('UPDATE notification SET isForward=:isForward where nid=:nid', {
      nid,
      isForward: 1,
    });
    await this.sendAllNotification(firebaseSend, {
      title: name,
      subtitle,
      topic: String(kkid),
    });
    return true;
  }

  // ---------------- Excel reports (ExcelJS) ----------------

  async _buildReportWorkbook(rows, sabhaCols, withAddress, titleFormatter) {
    const ExcelJS = require('exceljs');
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Sheet1');
    const headerBase = ['No.', 'Team', 'KK Name', 'KK Surname', 'Name', 'Middle Name', 'Surname', 'DOB', 'Mobile', 'Edu '];
    if (withAddress) headerBase.push('Address');
    headerBase.forEach((v, i) => {
      const cell = ws.getCell(1, i + 1);
      cell.value = v;
      cell.alignment = { horizontal: 'center' };
    });
    const baseCols = headerBase.length;

    for (let idx = 0; idx < rows.length; idx++) {
      const val = rows[idx];
      const home = withAddress
        ? await one(
            "SELECT CONCAT(a.address,',',a.city,',',a.state,',',a.country) home FROM yuvak y, address a where a.aid=y.aid AND y.yid=:yid",
            { yid: val.yid }
          )
        : null;
      const r = idx + 3;
      ws.getCell(r, 1).value = idx + 1;
      ws.getCell(r, 1).alignment = { horizontal: 'center' };
      ws.getCell(r, 2).value = val.tname;
      ws.getCell(r, 3).value = val.kkname;
      ws.getCell(r, 4).value = val.kksurname;
      ws.getCell(r, 5).value = val.name;
      ws.getCell(r, 6).value = val.fname;
      ws.getCell(r, 7).value = val.surname;
      ws.getCell(r, 8).value = val.dob;
      ws.getCell(r, 8).alignment = { horizontal: 'center' };
      ws.getCell(r, 9).value = val.mobile;
      ws.getCell(r, 9).alignment = { horizontal: 'center' };
      ws.getCell(r, 10).value = val.ename;
      if (withAddress) ws.getCell(r, 11).value = home ? home.home : '';

      let col = baseCols + 1;
      for (const k of sabhaCols) {
        const att = await one(`SELECT ${k} as p from attendance where yid=:yid`, { yid: val.yid });
        const sabha = await one('SELECT * from sabha where sid=:sid', {
          sid: String(k).replace(/^s/, ''),
        });
        const mergeStart = col;
        ws.getCell(2, col).value = 'Att';
        ws.getCell(2, col).alignment = { horizontal: 'center' };
        ws.getCell(r, col).value = att ? att.p : null;
        ws.getCell(r, col).alignment = { horizontal: 'center' };
        col++;
        ws.mergeCells(1, mergeStart, 1, col);
        ws.getCell(1, mergeStart).value = titleFormatter(k, sabha);
        ws.getCell(1, mergeStart).alignment = { horizontal: 'center' };
        ws.getCell(2, col).value = 'Remark';
        ws.getCell(2, col).alignment = { horizontal: 'center' };
        if (att && att.p == 2) {
          const re = await one(
            'SELECT * From remarks where sid=:sid and rel=2 and yid=:yid',
            { sid: String(k).replace(/^s/, ''), yid: val.yid }
          );
          ws.getCell(r, col).value = re ? re.remark : '';
          ws.getCell(r, col).alignment = { horizontal: 'center' };
        }
        col++;
      }
    }
    ws.columns.forEach((c) => {
      c.width = Math.max(10, c.width || 10);
    });
    return wb;
  }

  async yuvakXlReport(input) {
    try {
      const kkLevel = Number(input.kk_level);
      let rows;
      if (kkLevel === 1) {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname',
          { kk_level: kkLevel + 1 }
        );
      } else {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name',
          { kk_level: kkLevel, tid: input.tid }
        );
      }
      const column = await this.getSabhaByLevel1(this.level_1id);
      const wb = await this._buildReportWorkbook(rows, column.colA, true, (k, sabha) =>
        `${String(k).toUpperCase()} | ${sabha ? sabha.date : ''}`
      );

      if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });
      const fileName = `${input.kk_id}.xlsx`;
      await wb.xlsx.writeFile(path.join(REPORT_DIR, fileName));
      return { status: true, url: `${REPORT_BASE_URL}/${fileName}` };
    } catch (e) {
      return { status: false, url: '' };
    }
  }

  async yuvakSabhaReport(input) {
    try {
      const kkLevel = Number(input.kk_level);
      let rows;
      if (kkLevel === 1) {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname',
          { kk_level: kkLevel + 1 }
        );
      } else {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name',
          { kk_level: kkLevel, tid: input.tid }
        );
      }
      const colA = ['s' + input.sid];
      const wb = await this._buildReportWorkbook(rows, colA, false, (k, sabha) =>
        `${String(k).toUpperCase()} | ${sabha ? sabha.date : ''}`
      );

      if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });
      const fileName = `s${input.sid}.xlsx`;
      await wb.xlsx.writeFile(path.join(REPORT_DIR, fileName));
      return { status: true, url: `${REPORT_BASE_URL}/${fileName}` };
    } catch (e) {
      return { status: false, url: '' };
    }
  }

  async yuvakXlPadhramniReport(input) {
    try {
      const kkLevel = Number(input.kk_level);
      let rows;
      if (kkLevel === 1) {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname',
          { kk_level: kkLevel + 1 }
        );
      } else {
        rows = await sel(
          'SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name',
          { kk_level: kkLevel, tid: input.tid }
        );
      }
      const colA = [input.sid];
      const wb = await this._buildReportWorkbook(rows, colA, true, (k, sabha) =>
        `${sabha ? sabha.title : ''} | ${sabha ? sabha.date : ''}`
      );
      // Auto-filter the entire range, as the PHP version does.
      const ws = wb.getWorksheet(1);
      ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: ws.rowCount, column: ws.columnCount } };

      if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });
      const fileName = `${input.kk_id}.xlsx`;
      await wb.xlsx.writeFile(path.join(REPORT_DIR, fileName));
      return { status: true, url: `${REPORT_BASE_URL}/${fileName}` };
    } catch (e) {
      return { status: false, url: '' };
    }
  }
}

module.exports = { User };
