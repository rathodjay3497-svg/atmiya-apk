<?php
require 'firebase.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule;


class User
{
    private $table = 'yuvak';
    protected $conn;
    protected $irregular  = 0;
    protected $regular  = 0;

    protected $totalAtt = 0;
    protected $P = 0;
    protected $Ab = 0;
    protected $Un = 0;

    protected $level_1id = 1;
    function __construct($db)
    {

        $this->conn = $db;
    }
    public function checkLogin($mobile, $password)
    {
        try {
            $email_pass = "SELECT * FROM yuvak where mobile=:mobile AND password=:password AND isLogin=:isLogin";
            $stmt = $this->conn->prepare($email_pass);
            $stmt->bindValue(':mobile', $mobile);
            $stmt->bindValue(':password', $password);
            $stmt->bindValue(':isLogin', 1, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($row != "" && count($row) > 0) {
                $row['error'] = false;
            } else {
                $row['error'] = true;
            }

            $email_pass = "SELECT * FROM `mentor` WHERE `main_kk_id` = :main_kk_id";
            $stmt = $this->conn->prepare($email_pass);
            $stmt->bindValue(':main_kk_id', $row['yid']);
            $stmt->execute();
            $mainKKGroupCount = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($mainKKGroupCount != "" && count($mainKKGroupCount) > 0) {
                $row['isMentor'] = "1";
            } else {
                $row['isMentor'] = "0";
            }


            return $row;
        } catch (Exception $e) {
            $row['error'] = true;
            return $row;
        }
    }

    public function changePassword($input)
    {
        $yid = $input['yid'];
        $password = $input['password'];
        try {
            $sql = "UPDATE yuvak SET password=:password where yid=:yid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':password', $password);
            $stmt->bindValue(':yid', $yid);
            $stmt->execute();

            return true;
        } catch (Exception $e) {

            return false;
        }
    }

    public function insertDbManual($input)
    {
        $sid = $input['statid'];
        $eid = $input['endid'];
        try {

            for ($i = $sid; $i <= $eid; $i++) {

                print($i);
                print("App not enable ");
                // $sql = "INSERT INTO attendance SET yid=:yid";
                // $stmt = $this->conn->prepare($sql);
                // $stmt->bindValue(':yid', $i);
                // $stmt->execute();

                // $sql = "INSERT INTO contact SET yid=:yid";
                // $stmt = $this->conn->prepare($sql);
                // $stmt->bindValue(':yid', $i);
                // $stmt->execute();
            }

            return true;
        } catch (Exception $e) {

            return false;
        }
    }





    public function updateLogin($input)
    {
        $yid = $input['yid'];
        $status = $input['status'];
        try {
            $sql = "UPDATE yuvak SET isLogin=:isLogin where yid=:yid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':isLogin', $status);
            $stmt->bindValue(':yid', $yid);
            $stmt->execute();

            return true;
        } catch (Exception $e) {

            return false;
        }
    }

    //API or alos use in getYuvakBykk() in this file
    public function getConditons()
    {

        try {
            $sql = "select * from conditions";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        } catch (Exception $e) {
            $row = null;
            return $row;
        }
    }


    public function updateLongLate($input)
    {

        $longi = $input['longi'];
        $lat = $input['lat'];
        $yid = $input['yid'];


        try {
            $sql = "UPDATE yuvak SET longi=:longi, lat=:lat where yid=:yid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':longi', $longi);
            $stmt->bindValue(':lat', $lat);
            $stmt->bindValue(':yid', $yid);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            print($e);

            return false;
        }
    }

    public function getYuvakByTeam($input)
    {
        try {

            if ($input['kk_level'] == 1) {

                $column = $this->getSabhaByLevel1($this->level_1id);
                $column['totalSabha'] = $this->getMinSabha($column['totalSabha']);


                $input['kk_level'] =  $input['kk_level'] + 1;
                $email_pass = "SELECT * FROM yuvak y,teams t,address a where isDeleted=0 AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND a.aid = y.aid order by t.tname";
                $stmt = $this->conn->prepare($email_pass);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':kk_id', $input['kk_id']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);


                // Important  :: Same team yuvak at same level not count
                foreach ($row as $key => $value) {

                    // if ($column['totalSabha'] != 0) {
                    //     $sa = $this->getYuvakStatus($column, $value['yid']);
                    //     $row[$key]['sabhaSta'] = $sa['st'];
                    //     $row[$key]['sabhaMsg'] = $sa['sm'];
                    // } else {
                    //     $row[$key]['sabhaSta'] = "0";
                    //     $row[$key]['sabhaMsg'] = "Irregular";
                    // }

                    $sa = $this->getYuvakStatus($column, $value['yid']);
                    // $row[$key]['sabhaSta'] = "1";
                    $row[$key]['ssssssssssssssssssssssss'] = $sa;
                    if($sa['yuvakTotalSabha'] <= 8 ){
                        $row[$key]['sabhaSta'] = "1";
                    }elseif($column['totalSabha'] >= 9 && $sa['st'] >= 4){
                        $row[$key]['sabhaSta'] = "2";
                    }elseif($column['totalSabha'] >= 9 && $sa['st'] == 0){
                        $row[$key]['sabhaSta'] = "4";
                    }elseif($column['totalSabha'] >= 9 && $sa['st'] < 3){
                        $row[$key]['sabhaSta'] = "3";
                    }
                    
                    //Sabha Status
                    $email_pass = "SELECT count(*) c FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.tid=:tid";
                    $stmt = $this->conn->prepare($email_pass);
                    $stmt->bindValue(':kk_level', $value['kk_level']);
                    $stmt->bindValue(':tid', $value['tid']);
                    $stmt->execute();
                    $r = $stmt->fetch(PDO::FETCH_ASSOC);
                    $row[$key]['totalSubYuvak'] = $r['c'];

                    $row[$key]['totalSubYuvak'] = $this->getSubYuvakSabhaStatus($value['yid'], $value['kk_level'], $value['tid'], $column);
                    $row[$key]['subRegular'] = $this->regular;
                    $row[$key]['subIrregular'] = $this->irregular;
                    $this->regular = 0;
                    $this->irregular = 0;
                }
            } else {


                $column = $this->getSabhaByLevel1($this->level_1id);
                $column['totalSabha'] = $this->getMinSabha($column['totalSabha']);

                $input['kk_level'] =  $input['kk_level'] + 1;

                $email_pass = "SELECT * FROM yuvak y,teams t,address a where isDeleted=0 AND  y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND y.tid=:tid AND a.aid = y.aid  order by y.name";
                $stmt = $this->conn->prepare($email_pass);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':kk_id', $input['kk_id']);
                $stmt->bindValue(':tid',  $input['tid']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Important  :: Same team yuvak at same level not count
                foreach ($row as $key => $value) {
                    //Sabha Status

                    // if ($column['totalSabha'] != 0) {
                    //     $sa = $this->getYuvakStatus($column, $value['yid']);
                    //     $row[$key]['sabhaSta'] = $sa['st'];
                    //     $row[$key]['sabhaMsg'] = $sa['sm'];
                    // } else {
                    //     $row[$key]['sabhaSta'] = "0";
                    //     $row[$key]['sabhaMsg'] = "Irregular";
                    // }

                    $sa = $this->getYuvakStatus($column, $value['yid']);
                    $row[$key]['sabhaSta'] = "1";
                    $row[$key]['ssssssssssssssssssssssss'] = $sa;
                        if($sa['yuvakTotalSabha'] <= 8 ){
                            $row[$key]['sabhaSta'] = "1";
                        }elseif($column['totalSabha'] >= 9 && $sa['st'] >= 4){
                            $row[$key]['sabhaSta'] = "2";
                        }elseif($column['totalSabha'] >= 9 && $sa['st'] == 0){
                            $row[$key]['sabhaSta'] = "4";
                        }elseif($column['totalSabha'] >= 9 && $sa['st'] < 3){
                            $row[$key]['sabhaSta'] = "3";
                        }
                        
        
                    
                    
                    $email_pass = "SELECT count(*) c FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.tid=:tid";
                    $stmt = $this->conn->prepare($email_pass);
                    $stmt->bindValue(':kk_level', $value['kk_level']);
                    $stmt->bindValue(':tid', $value['tid']);
                    $stmt->execute();
                    $r = $stmt->fetch(PDO::FETCH_ASSOC);
                    $row[$key]['totalSubYuvak'] = $r['c'];

                    $row[$key]['totalSubYuvak'] = $this->getSubYuvakSabhaStatus($value['yid'], $value['kk_level'], $value['tid'], $column);
                    $row[$key]['subRegular'] = $this->regular;
                    $row[$key]['subIrregular'] = $this->irregular;
                    $this->regular = 0;
                    $this->irregular = 0;

                    //Find Total Sub Yuvak

                }
            }

            return $row;
        } catch (Exception $e) {
            $row = [];
            $this->regular = 0;
            $this->irregular = 0;
            return $row;
        }
    }
    
    public function getYuvakByCategory($input)
    {
        try {

            $column = $this->getSabhaByLevel1($this->level_1id);
            $column['totalSabha'] = $this->getMinSabha($column['totalSabha']);

            $input['kk_level'] =  $input['kk_level'] + 1;

            if($input['kk_level'] <= 3){
                $email_pass = "SELECT * FROM yuvak y,teams t,address a where y.tid = t.tid AND  y.kk_level=:kk_level  AND a.aid = y.aid AND y.type =:type  order by y.name";
                $stmt = $this->conn->prepare($email_pass);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':type', $input['type']);
            }else{
                $email_pass = "SELECT * FROM yuvak y,teams t,address a where y.tid = t.tid AND  y.kk_level=:kk_level  AND y.kk_id=:kk_id AND a.aid = y.aid AND y.type =:type   order by y.name";
                $stmt = $this->conn->prepare($email_pass);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':kk_id', $input['kk_id']); 
                $stmt->bindValue(':type', $input['type']);
            }
            
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($row as $key => $value) {
                

                $sa = $this->getYuvakStatus($column, $value['yid']);
                $row[$key]['sabhaSta'] = "1";
                if($sa['yuvakTotalSabha'] <= 8 ){
                    $row[$key]['sabhaSta'] = "1";
                }elseif($column['totalSabha'] >= 9 && $sa['st'] >= 4){
                    $row[$key]['sabhaSta'] = "2";
                }elseif($column['totalSabha'] >= 9 && $sa['st'] == 0){
                    $row[$key]['sabhaSta'] = "4";
                }elseif($column['totalSabha'] >= 9 && $sa['st'] < 3){
                    $row[$key]['sabhaSta'] = "3";
                }
                    
    
                $email_pass = "SELECT count(*) c FROM yuvak y where y.kk_level>:kk_level  AND y.type =:type";
                $stmt = $this->conn->prepare($email_pass);
                $stmt->bindValue(':kk_level', $value['kk_level']);
                $stmt->bindValue(':type', $input['type']);
                // $stmt->bindValue(':tid', $value['tid']);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                $row[$key]['totalSubYuvak'] = $r['c'];

                $row[$key]['totalSubYuvak'] = $this->getSubYuvakSabhaStatusByCategory($value['yid'], $value['kk_level'], $value['tid'], $column);
                $row[$key]['subRegular'] = $this->regular;
                $row[$key]['subIrregular'] = $this->irregular;
                $this->regular = 0;
                $this->irregular = 0;

            }

            return $row;
        } catch (Exception $e) {
            $row = [];
            $this->regular = 0;
            $this->irregular = 0;
            return $row;
        }
    }

    public function getSearchYuvak($input)
    {

        if ($input['kk_level'] == 1 and $input['type']  == 'name') {
            $q = "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.name Like '%" . $input['query'] . "%' order by t.tname";
            $stmt = $this->conn->prepare($q);
            $stmt->bindValue(':kk_level', $input['kk_level']);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($input['kk_level'] == 1 and $input['type']  == 'mobile') {
            $q = "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND  y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.mobile Like '%" . $input['query'] . "%' order by t.tname";
            $stmt = $this->conn->prepare($q);
            $stmt->bindValue(':kk_level', $input['kk_level']);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($input['type']  == 'name') {
            $q = "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.tid=:tid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.name Like '%" . $input['query'] . "%' order by t.tname";
            $stmt = $this->conn->prepare($q);
            $stmt->bindValue(':kk_level', $input['kk_level']);
            $stmt->bindValue(':tid', $input['tid']);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($input['type']  == 'mobile') {
            $q = "SELECT y.*,t.*,a.*,kk.name As kkFname, kk.surname As kkSurname FROM yuvak y,teams t,address a, yuvak kk  where y.kk_id = kk.yid AND y.tid=:tid AND y.kk_level>:kk_level AND t.tid=y.tid AND a.aid = y.aid AND y.mobile Like '%" . $input['query'] . "%' order by t.tname";
            $stmt = $this->conn->prepare($q);
            $stmt->bindValue(':kk_level', $input['kk_level']);
            $stmt->bindValue(':tid', $input['tid']);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }


        return $rows;
    }


    public function getSubYuvakSabhaStatusByCategory($kk_id, $kk_level, $tid, $column)
    {

        $email_pass = "SELECT * FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.kk_id = :kk_id AND (y.type IN ('Kishor Mandal'))";
        $stmt = $this->conn->prepare($email_pass);
        $stmt->bindValue(':kk_id', $kk_id);
        $stmt->bindValue(':kk_level', $kk_level);
        // $stmt->bindValue(':tid', $tid);
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //Main Sub Yuvak Count
        $yc = count($r);

        //Find Sub yuvak then Sub Yuvak Count
        foreach ($r as $key => $val) {
            $kkLevel = $val['kk_level'];
            $ttid = $val['tid'];
            $kkId = $val['yid'];

            if (count($r) != 0) {
                if ($column['totalSabha'] != 0) {
                    $sa = $this->getYuvakStatus($column, $kkId);
                    if ($sa['st'] == 1) {
                        $this->regular = $this->regular + 1;
                    } else if ($sa['st'] == 0) {
                        $this->irregular = $this->irregular + 1;
                    }
                } else {
                    $this->irregular = $this->irregular + 1;
                }

                $yc = $yc + $this->getSubYuvakSabhaStatusByCategory($kkId, $kkLevel, $ttid, $column);
            } else {
                return  $yc;
            }
        }
        return $yc;
    }
    
    public function getSubYuvakSabhaStatus($kk_id, $kk_level, $tid, $column)
    {

        $email_pass = "SELECT * FROM yuvak y where isDeleted=0 AND  y.kk_level>:kk_level AND y.tid=:tid AND y.kk_id=:kk_id";
        $stmt = $this->conn->prepare($email_pass);
        $stmt->bindValue(':kk_id', $kk_id);
        $stmt->bindValue(':kk_level', $kk_level);
        $stmt->bindValue(':tid', $tid);
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //Main Sub Yuvak Count
        $yc = count($r);

        //Find Sub yuvak then Sub Yuvak Count
        foreach ($r as $key => $val) {
            $kkLevel = $val['kk_level'];
            $ttid = $val['tid'];
            $kkId = $val['yid'];

            if (count($r) != 0) {
                if ($column['totalSabha'] != 0) {
                    $sa = $this->getYuvakStatus($column, $kkId);
                    if ($sa['st'] == 1) {
                        $this->regular = $this->regular + 1;
                    } else if ($sa['st'] == 0) {
                        $this->irregular = $this->irregular + 1;
                    }
                } else {
                    $this->irregular = $this->irregular + 1;
                }

                $yc = $yc + $this->getSubYuvakSabhaStatus($kkId, $kkLevel, $ttid, $column);
            } else {
                return  $yc;
            }
        }
        return $yc;
    }





    //Funcation use in getYuvakByTeam()
    public function getMinSabha($totalSabha)
    {
        $row = $this->getConditons();
        $maxSabha = $row['maxSabha'];
        $minSabha = $row['minSabha'];
        $regularSabha = round(($minSabha / $maxSabha) * $totalSabha);

        return $regularSabha;
    }

    //Funcation use in getYuvakByTeam()
    public function getYuvakStatus($column, $yid)
    {
        $str = "SELECT cdt  FROM yuvak  where yid=" . $yid;
        $stmt = $this->conn->prepare($str);
        $stmt->execute();
        $ycdt = $stmt->fetch(PDO::FETCH_ASSOC);


        $str = "SELECT COUNT(*) AS yuvakTotalSabha FROM sabha WHERE kk_id = 1 AND date >= DATE(:date)";
        // $str = "SELECT COUNT(*) AS yuvakTotalSabha FROM sabha WHERE kk_id = 1 AND date >= Date('2023-07-01');";
        $stmt = $this->conn->prepare($str);
        $stmt->bindValue(':date', $ycdt['cdt'],PDO::PARAM_STR);
        $stmt->execute();
        $yuvakTotalSabha = $stmt->fetch(PDO::FETCH_ASSOC);

        $str = "SELECT " . $column['col'] . " FROM attendance  where yid=" . $yid;
        $stmt = $this->conn->prepare($str);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);



        // return $row['total'];
        $yuvakAttSabha = $row['total'];
        $minSabha = $column['totalSabha'];

        $out['st'] = $yuvakAttSabha;
        $out['yuvakTotalSabha'] = $yuvakTotalSabha['yuvakTotalSabha'];
        // if ($yuvakAttSabha >= $minSabha) {
        //     $out['sm'] = "Regular";
        //     $out['st'] = "1";

        //     // print($yid."\n\n");
        //     //   print_r($out);
        //     return $out;
        // } else {
        //     // $out['sm'] = "Irregular";
        //     $out['sm'] = $yuvakAttSabha;

        //     $out['st'] = "0";
        //     //   print($yid."\n\n");
        //     //   print_r($out);
        //     return $out;
        // }
        // print_r($out);
        return $out;
    }


    //Funcation use in getYuvakByTeam()
    public function getSabhaByLevel1($kk_id)
    {


        //Find All Created Sabha by kk level 1
        $sql = "SELECT * FROM sabha s where s.kk_id=:kk_id AND isSabha=1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kk_id', $kk_id);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $newArr = [];
        $columnName = "(";
        foreach ($row as $k => $v) {
            if ($k === array_key_last($row)) {
                $columnName = $columnName . "COUNT(CASE WHEN s" . $v['sid'] . "=1 THEN 1 END)";
            } else {
                $columnName = $columnName . "COUNT(CASE WHEN s" . $v['sid'] . "=1 THEN 1 END)+";
            }

            array_push($newArr, "s" . $v['sid']);
        }
        $columnName .= ") as total";

        $out['col'] =  $columnName;
        $out['totalSabha'] =  count($row);
        $out['colA'] =  $newArr;
        return $out;
    }

    public function getEduOptions()
    {
        try {
            $qu = "SELECT * FROM education WHERE isshow=1 AND pid = 0";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute();
            $rowMainData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rowMainData as $key=>$val){
                $qu = "SELECT * FROM education WHERE isshow = 1 AND pid = :pid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':pid', $val['eid']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $rowMainData[$key]['subData'] = $row;
 
            }
            return $rowMainData;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }
    
    public function getYuvakCategoryOptions()
    {
        try {
            $qu = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'yuvak' 
            AND COLUMN_NAME = 'type'";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $enumValues = $result['COLUMN_TYPE'];
            preg_match("/^enum\((.*)\)$/", $enumValues, $matches);
            $enumValues = explode(",", str_replace("'", "", $matches[1]));

            return $enumValues;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }

    public function getMentorTeams($kkid)
    {
        try {
            $qu = "SELECT mentor.*,t.tname,t.tid FROM `mentor`,yuvak y,teams t WHERE y.tid = t.tid AND sub_kk_id = y.yid AND main_kk_id = :kkid";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':kkid', $kkid);
            $stmt->execute();
            $rowMainData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
            return $rowMainData;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }
    public function getTeamsOption()
    {
        try {
            $qu = "SELECT * FROM teams WHERE  NOT tid IN (SELECT DISTINCT(tid) FROM yuvak) ORDER BY tname";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $row;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }

    public function addYuvak($yuvak)
    {
        try {


            //Check Phone Number (Unique)
            $qu = "SELECT * FROM yuvak where mobile=:mobile";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':mobile', $yuvak['mobile']);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($row) == 0) {
                $sql = " INSERT INTO address SET 
    			            address=:address, city=:city, state=:state, pincode=:pincode, country=:country";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':address', $yuvak['address']);
                $stmt->bindValue(':city', $yuvak['city']);
                $stmt->bindValue(':state', $yuvak['state']);
                $stmt->bindValue(':pincode', $yuvak['pincode']);
                $stmt->bindValue(':country', $yuvak['country']);
                $stmt->execute();

                //Address Id
                $aid = $this->conn->lastInsertId();

                $sql = " INSERT INTO yuvak SET 
    			            name=:name, fname=:fname, surname=:surname, dob=:dob, mobile=:mobile, tid=:tid, kk_level=:kk_level, eid=:eid,seid=:seid, kk_id=:kk_id,aid=:aid,edesc=:edesc,eyear=:eyear,emark=:emark,type=:yuvakType";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':name', $yuvak['name']);
                $stmt->bindValue(':fname', $yuvak['fname']);
                $stmt->bindValue(':surname', $yuvak['surname']);
                $stmt->bindValue(':dob', $yuvak['dob']);
                $stmt->bindValue(':mobile', $yuvak['mobile']);
                $stmt->bindValue(':kk_id', $yuvak['kk_id']);
                $stmt->bindValue(':tid', $yuvak['tid']);
                $stmt->bindValue(':kk_level', $yuvak['kk_level']);
                $stmt->bindValue(':eid', $yuvak['eid']);
                $stmt->bindValue(':seid', $yuvak['seid']);
                $stmt->bindValue(':aid', $aid);
                $stmt->bindValue(':emark', $yuvak['emark']);
                $stmt->bindValue(':eyear', $yuvak['eyear']);
                $stmt->bindValue(':edesc', $yuvak['edesc']);
                $stmt->bindValue(':yuvakType', $yuvak['yuvakType']);
                $stmt->execute();
                $yid = $this->conn->lastInsertId();

                //For Att new Entry 
                $sql = " INSERT INTO attendance SET yid=:yid";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':yid', $yid);
                $stmt->execute();

                //For Random Selectin Entry
                $sql = " INSERT INTO contact SET yid=:yid";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':yid', $yid);
                $stmt->execute();


                //KK Column Check in contact
                $kkId = "k" . $yuvak['kk_id'];
                $sql = "SHOW COLUMNS FROM contact WHERE Field =:Field";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':Field', $kkId, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columnName = $kkId;
                if (count($row) == 0) {
                    //KK Column add in contact
                    $str =  "ALTER TABLE contact ADD " . $columnName . " INT(1) DEFAULT 0 after yid ";
                    $sql = $str;
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                }

                if ($yuvak['isImage'] == 'true') {
                    $imgeName = $yid . "." . pathinfo($_FILES["image"]['name'], PATHINFO_EXTENSION);
                    $target_dir = "../profiles/";
                    $target_dir = $target_dir . $imgeName;
                    $uploadOk = 1;

                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir)) {

                        $sql = "UPDATE yuvak SET img=:img where yid=:yid";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bindValue(':img', $imgeName);
                        $stmt->bindValue(':yid', $yid);
                        $stmt->execute();
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            print($e);

            return false;
        }
    }


    public function updateYuvak($yuvak)
    {
        try {

            $sql = " UPDATE  address SET 
			            address=:address, city=:city, state=:state, pincode=:pincode, country=:country where aid=:aid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':address', $yuvak['address']);
            $stmt->bindValue(':city', $yuvak['city']);
            $stmt->bindValue(':state', $yuvak['state']);
            $stmt->bindValue(':pincode', $yuvak['pincode']);
            $stmt->bindValue(':country', $yuvak['country']);
            $stmt->bindValue(':aid', $yuvak['aid']);
            $stmt->execute();

            //Address Id
            //$aid = $this->conn->lastInsertId();

            $sql = " UPDATE  yuvak SET 
			   name=:name, fname=:fname, surname=:surname, dob=:dob, eid=:eid,seid=:seid,edesc=:edesc,eyear=:eyear,emark=:emark,type=:yuvakType where yid=:yid";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':name', $yuvak['name']);
            $stmt->bindValue(':fname', $yuvak['fname']);
            $stmt->bindValue(':surname', $yuvak['surname']);
            $stmt->bindValue(':dob', $yuvak['dob']);
            $stmt->bindValue(':eid', $yuvak['eid']);
            $stmt->bindValue(':seid', $yuvak['seid']);
            $stmt->bindValue(':yid', $yuvak['yid']);
            $stmt->bindValue(':emark', $yuvak['emark']);
            $stmt->bindValue(':eyear', $yuvak['eyear']);
            $stmt->bindValue(':edesc', $yuvak['edesc']);
            $stmt->bindValue(':yuvakType', $yuvak['yuvakType']);
            $stmt->execute();
            $yid = $yuvak['yid'];


            if ($yuvak['isImage'] == 'true') {
                $imgeName = $yid . "." . pathinfo($_FILES["image"]['name'], PATHINFO_EXTENSION);
                $target_dir = "../profiles/";
                $target_dir = $target_dir . $imgeName;
                $uploadOk = 1;


                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir)) {

                    $sql = "UPDATE yuvak SET img=:img where yid=:yid";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindValue(':img', $imgeName);
                    $stmt->bindValue(':yid', $yid);
                    $stmt->execute();
                    return true;
                } else {
                    return false;
                }
            }


            if ($yuvak['newMobileNumber'] != $yuvak['mobile']) {


                //Check Phone Number (Unique)
                $qu = "SELECT * FROM yuvak where mobile=:mobile";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':mobile', $yuvak['mobile']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($row) == 0) {
                    $sql = " UPDATE  yuvak SET  mobile=:mobile where yid=:yid";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindValue(':yid', $yuvak['yid']);
                    $stmt->bindValue(':mobile', $yuvak['mobile']);
                    $stmt->execute();
                    return true;
                } else {
                    return 'mobile';
                }
            }
            return true;
        } catch (Exception $e) {
            print($e);

            return false;
        }
    }


    public function addRemarks($remark)
    {
        try {
            if ($remark['rel'] == 2) {
                // $sql = " INSERT INTO remarks SET 
    			         //   yid=:yid, remark=:remark, rel=:rel,sid=:sid";
                // $stmt = $this->conn->prepare($sql);
                // $stmt->bindValue(':yid', $remark['yid']);
                // $stmt->bindValue(':remark', $remark['remark']);
                // $stmt->bindValue(':rel', $remark['rel']);
                // $stmt->bindValue(':sid', $remark['sid']);
                // $stmt->execute();
                
                // Check if the record exists first
                $checkSql = "SELECT COUNT(*) FROM remarks WHERE yid = :yid AND sid = :sid";
                $checkStmt = $this->conn->prepare($checkSql);
                
                // Bind the values for checking existence
                $checkStmt->bindValue(':yid', $remark['yid']);
                $checkStmt->bindValue(':sid', $remark['sid']);
                $checkStmt->execute();
                
                // Fetch result to see if the record exists
                $recordExists = $checkStmt->fetchColumn();
                
                if ($recordExists > 0) {
                    // Record exists, update the existing record
                    $sql = "UPDATE remarks SET remark = :remark, rel = :rel WHERE yid = :yid AND sid = :sid";
                } else {
                    // Record does not exist, insert a new one
                    $sql = "INSERT INTO remarks (yid, remark, rel, sid) VALUES (:yid, :remark, :rel, :sid)";
                }
                
                // Prepare the statement
                $stmt = $this->conn->prepare($sql);
                
                // Bind the values
                $stmt->bindValue(':yid', $remark['yid']);
                $stmt->bindValue(':remark', $remark['remark']);
                $stmt->bindValue(':rel', $remark['rel']);
                $stmt->bindValue(':sid', $remark['sid']);
                
                // Execute the statement
                $stmt->execute();

            } else if ($remark['rel'] == 3) {
                $sql = " INSERT INTO remarks SET 
    			            yid=:yid, remark=:remark, rel=:rel,taskid=:taskid";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':yid', $remark['yid']);
                $stmt->bindValue(':remark', $remark['remark']);
                $stmt->bindValue(':rel', $remark['rel']);
                $stmt->bindValue(':taskid', $remark['taskid']);
                $stmt->execute();
            } else {
                $sql = " INSERT INTO remarks SET 
    			            yid=:yid, remark=:remark, rel=:rel";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':yid', $remark['yid']);
                $stmt->bindValue(':remark', $remark['remark']);
                $stmt->bindValue(':rel', $remark['rel']);
                $stmt->execute();
            }
            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function updateRemarks($remark)
    {
        try {
            $sql = " UPDATE  remarks SET remark=:remark, rel=:rel where  rid=:rid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':rid', $remark['rid']);
            $stmt->bindValue(':remark', isset($remark['remark']) && !empty($remark['remark']) ? $remark['remark'] : '');
            $stmt->bindValue(':rel', $remark['rel']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function getRemarkByYuvak($remark)
    {
        try {
            $qu = "SELECT * FROM remarks where yid=:yid ORDER BY cdt DESC";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':yid', $remark['yid']);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $row;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function getOnlyKK($kk_level,$kktid)
    {
        $login = "1";
        try {
            if($kk_level == "1"){
                $qu = "SELECT * FROM yuvak y,teams t WHERE y.isLogin=:isLogin AND  t.tid=y.tid  ORDER BY y.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':isLogin', $login);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }else {
                $qu = "SELECT * FROM yuvak y,teams t WHERE y.isLogin=:isLogin AND  t.tid=y.tid AND y.kk_level >= 3 ANd y.tid = :kktid ORDER BY y.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':isLogin', $login);
                $stmt->bindValue(':kktid', $kktid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
           

            return $row;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }


    public function changeTeam($input)
    {
        $old_kk = $input['old_kk'];
        $new_kk = $input['new_kk'];

        try {
            $this->changeTeamData($old_kk, $new_kk);

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }

    //Use in change team function.
    public function changeTeamData($kk_id, $new_kk_id)
    {

        $qu = "SELECT * FROM yuvak where yid=:yid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $new_kk_id);
        $stmt->execute();
        $newkk = $stmt->fetch(PDO::FETCH_ASSOC);

        $newTid = $newkk['tid'];
        $newKKId = $newkk['yid'];
        $new_kk_level = $newkk['kk_level'] + 1;


        $sql = "UPDATE yuvak SET tid=:tid, kk_level=:kk_level, kk_id=:kk_id where yid=:yid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':tid', $newTid);
        $stmt->bindValue(':kk_level', $new_kk_level);
        $stmt->bindValue(':kk_id', $newKKId);
        $stmt->bindValue(':yid', $kk_id);
        $stmt->execute();

        $qu = "SELECT * FROM yuvak where kk_id=:kk_id";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':kk_id', $kk_id);
        $stmt->execute();
        $newsubkk = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($newsubkk) != 0) {
            foreach ($newsubkk as $subkk) {
                $this->changeTeamData($subkk['yid'], $kk_id);
                // print("\nFor ".$subkk['yid']."\n");
                // print("tid ".$newTid." ");
                // print("kk_level ".$new_kk_level." ");
                // print("KKID ".$newKKId." ");
            }
        } else {
            return;
        }
    }



    public function addSabha($firebase, $input)
    {

        try {
            $sql = " INSERT INTO sabha SET 
			            kk_id=:kk_id, title=:title, date=:date,time=:time,isSabha=:isSabha";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':kk_id', $input['kk_id']);
            $stmt->bindValue(':title', $input['title']);
            $stmt->bindValue(':date', $input['date']);
            $stmt->bindValue(':time', $input['time']);
            $stmt->bindValue(':isSabha', $input['isSabha']);
            $stmt->execute();
            $sid = $this->conn->lastInsertId();
            $columnName = 's' . $sid;

            //Add New Column For Attendance
            $str =  "ALTER TABLE attendance ADD " . $columnName . " INT(1) DEFAULT 0 after yid ";
            $sql = $str;
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            //$this->sendSabhaNotification($firebase,$input['kk_id'], $input['title']);
            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function sendSabhaNotification($firebase, $input)
    {
        $kk_id = $input['kk_id'];
        $title = $input['title'];


        $qu = "SELECT * FROM yuvak where yid=:yid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $kk_id);
        $stmt->execute();
        $kkDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kkDetails['kk_level'] == 1) {
            $qu = "SELECT * FROM yuvak where isLogin=1";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute();
            $subkkDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $input['title'] =  $title . ",The new sabha was created by " . $kkDetails['name'] . " " . $kkDetails['surname'] . ".";
            $input['subtitle'] = "Please fill the Attdence.";
            foreach ($subkkDetails as $key => $value) {
                $input['topic'] = $value['yid'];
                $this->sendAllNotification($firebase, $input);
            }
        } else {
        }
    }
    public function updateSabha($input)
    {
        try {
            $sql = " UPDATE sabha SET 
			            title=:title, date=:date,time=:time,isSabha=:isSabha where sid=:sid ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':sid', $input['sid']);
            $stmt->bindValue(':title', $input['title']);
            $stmt->bindValue(':date', $input['date']);
            $stmt->bindValue(':time', $input['time']);
            $stmt->bindValue(':isSabha', $input['isSabha']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }
    
    public function deleteYuvak($input)
    {
        try {
            $sql = " UPDATE yuvak SET 
			            isDeleted=:isDelete where yid=:yid ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':yid', $input['yid']);
            $stmt->bindValue(':isDelete', $input['isDelete']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function getSabhaByKK($input)
    {
        try {

            //GET KK Details
            $qu = "SELECT * FROM yuvak where yid=:yid";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':yid', $input['kk_id']);
            $stmt->execute();
            $kkDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($kkDetails['kk_level'] == 1) {
                $qu = "SELECT * FROM sabha where isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak where  (isLogin=1 AND kk_level<:kk_level ) OR yid=:yid) order by cdt desc";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                $stmt->bindValue(':yid', $input['kk_id']);
                //  $stmt->bindValue(':tid', $kkDetails['tid']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $qu = "SELECT * FROM sabha where isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak where isLogin=1 AND ((tid=:tid AND kk_level<=:kk_level) OR (kk_level<=:kk_level AND kk_level=1))) order by cdt desc";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                // $stmt->bindValue(':yid', $input['kk_id']);
                $stmt->bindValue(':tid', $kkDetails['tid']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }



            foreach ($row as $key => $value) {
                $cname = $value['sid'];
                $qu = "SELECT count(*) as t, COUNT(CASE WHEN s" . $cname . "=1 THEN 1 END) as p,COUNT(CASE WHEN s" . $cname . "=0 THEN 1 END) as un,COUNT(CASE WHEN s" . $cname . "=2 THEN 1 END) as ab FROM attendance";
                $stmt = $this->conn->prepare($qu);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                $row[$key]['totalYuvak'] = $r['t'];
                $row[$key]['present'] = $r['p'];
                $row[$key]['absent'] = $r['ab'];
                $row[$key]['undefine'] = $r['un'];
            }
            return $row;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function getAttYuvak($input)
    {
        try {
            if ($input['kk_level'] == 1) {
                $input['kk_level'] =  $input['kk_level'] + 1;

                $email_pass = "SELECT y.*,t.*,at.s" . $input['sid'] . " status FROM yuvak y,teams t,address a,attendance at where y.yid=at.yid AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND a.aid = y.aid order by y.name";
                $qu = $email_pass;
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':kk_id', $input['kk_id']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($row as $k => $r) {
                    $row[$k]['status'] = $r['status'] . "";
                    $q = "SELECT * FROM remarks where yid=:yid AND sid=:sid";
                    $stmt = $this->conn->prepare($q);
                    $stmt->bindValue(':yid', $r['yid']);
                    $stmt->bindValue(':sid', $input['sid']);
                    $stmt->execute();
                    $reDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($reDetails != "") {
                        $row[$k]['remark'] = $reDetails['remark'];
                    }
                }
            } else {
                $input['kk_level'] =  $input['kk_level'] + 1;

                $email_pass = "SELECT y.*,t.*,at.s" . $input['sid'] . " status  FROM yuvak y,teams t,address a,attendance at where   y.yid=at.yid AND y.kk_id=:kk_id AND y.kk_level=:kk_level AND t.tid=y.tid AND y.tid=:tid AND a.aid = y.aid order by y.name";
                $qu = $email_pass;
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $input['kk_level']);
                $stmt->bindValue(':kk_id', $input['kk_id']);
                $stmt->bindValue(':tid',  $input['tid']);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($row as $k => $r) {
                    $row[$k]['status'] = $r['status'] . "";
                    $q = "SELECT * FROM remarks where yid=:yid AND sid=:sid";
                    $stmt = $this->conn->prepare($q);
                    $stmt->bindValue(':yid', $r['yid']);
                    $stmt->bindValue(':sid',  $input['sid']);
                    $stmt->execute();
                    $reDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($reDetails != "") {

                        $row[$k]['remark'] = $reDetails['remark'];
                    }
                }
            }


            foreach ($row as $key => $value) {
                $sid = $input['sid'];
                $qu = "SELECT count(*) as t, COUNT(CASE WHEN s" . $sid . "=1 THEN 1 END) as p,COUNT(CASE WHEN s" . $sid . "=0 THEN 1 END) as un,COUNT(CASE WHEN s" . $sid . "=2 THEN 1 END) as ab FROM attendance where yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['yid']);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);

                $row[$key]['stotal'] = $r['t'] . "";
                $row[$key]['sP'] = $r['p'] . "";
                $row[$key]['sAb'] = $r['ab'] . "";
                $row[$key]['sUn'] = $r['un'] . "";


                //Finde Sub Yuvak Att
                $this->findSubYuvak($value['yid'], $input['sid']);
                $row[$key]['total'] = $this->totalAtt;
                $row[$key]['P'] = $this->P;
                $row[$key]['Ab'] = $this->Ab;
                $row[$key]['Un'] = $this->Un;
                $this->totalAtt = 0;
                $this->P = 0;
                $this->Ab = 0;
                $this->Un = 0;
            }



            return $row;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }


    public function findSubYuvak($kkId, $sid)
    {

        $sql = "SELECT * FROM yuvak where kk_id=:kk_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kk_id', $kkId);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);


        if (count($row) != 0) {
            foreach ($row as $key => $value) {

                $qu = "SELECT count(*) as t, COUNT(CASE WHEN s" . $sid . "=1 THEN 1 END) as p,COUNT(CASE WHEN s" . $sid . "=0 THEN 1 END) as un,COUNT(CASE WHEN s" . $sid . "=2 THEN 1 END) as ab FROM attendance where yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['yid']);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->totalAtt = $this->totalAtt + $r['t'];
                $this->P = $this->P + $r['p'];
                $this->Ab  =  $this->Ab  + $r['ab'];
                $this->Un =  $this->Un + $r['un'];
                $this->findSubYuvak($value['yid'], $sid);
            }
        } else {
            return;
        }
    }

    public function updateAtt($input)
    {
        $sid = "s" . $input['sid'];


        try {
            $sql = " UPDATE  attendance SET " . $sid . "=:status where  yid=:yid";
            $q = $sql;
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':yid', $input['yid']);
            $stmt->bindValue(':status', $input['status']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function sendAllNotification($firebase, $input)
    {
        try {

            $notifier = new FirebaseNotifier(__DIR__ . '/atmiyayuvak.json');

            $title = htmlspecialchars($input['title'], ENT_COMPAT);
            $body = htmlspecialchars($input['subtitle'], ENT_COMPAT);
            $topic = htmlspecialchars($input['topic'], ENT_COMPAT);

            $response = $notifier->sendNotification($topic, $title, $body);
            print_r($response);
            return  true;
        } catch (Exception $e) {
            // Print error message and return false
            print("Error: " . $e->getMessage());
            return false;
        }
    }


    public function sendBdayNotification($firebase)
    {
        try {

            //Find Yuvak Details
            date_default_timezone_set("Asia/Calcutta");
            $date =  date("Y-m-d");


            //print($date);
            $qu = "SELECT y.* FROM yuvak y,teams t where t.tid=y.tid AND  DATE_FORMAT(DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),'-', MONTH(dob),'-', DAY(dob))),'%Y-%m-%d')=:date";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':date', $date);
            $stmt->execute();

            $yuvakIds = $stmt->fetchAll(PDO::FETCH_ASSOC);



            foreach ($yuvakIds as $key => $value) {

                //Finde KK Details
                $qu = "SELECT name,surname FROM yuvak where yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['kk_id']);
                $stmt->execute();
                $kk = $stmt->fetch(PDO::FETCH_ASSOC);


                $name = $value['name'] . " " . $value['surname'];
                $kkname = $kk['name'] . " " . $kk['surname'];
                $tname = $value['tname'];
                $input['title'] = "$name";
                $input['subtitle'] = "Birthday : " . $value['dob'] . "\n$kkname\n$tname";
                $input['topic'] = $value['kk_id'];

                //print_r($input);
                if ($kk['kk_level'] == 1) {
                    $sql = " INSERT INTO notification SET 
			            yid=:yid, kk_id=:kk_id, isForward=:isForward";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindValue(':yid', $value['yid']);
                    $stmt->bindValue(':kk_id', $value['kk_id']);
                    $stmt->bindValue(':isForward', 1);
                    $stmt->execute();
                } else {

                    $sql = " INSERT INTO notification SET 
			            yid=:yid, kk_id=:kk_id, isForward=:isForward";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindValue(':yid', $value['yid']);
                    $stmt->bindValue(':kk_id', $value['kk_id']);
                    $stmt->bindValue(':isForward', 0);
                    $stmt->execute();
                }


                $nid = $this->conn->lastInsertId();

                print_r($nid . '<br>');
                $this->sendAllNotification($firebase, $input);
            }
            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function getRandomYuvak($firebase)
    {
        $login = "1";
        $maxYuvak = 3;
        try {
            //Find All Column For KK
            $sql = "SHOW COLUMNS FROM contact";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($row as $key => $value) {



                if ($value['Field'] != "yid" and $value['Field'] != 'cid') {

                    //Find KK Id
                    $kkId = trim($value['Field'], "k");

                    //Find KK Details
                    $sql = "SELECT *  FROM yuvak where yid=:yid";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindValue(':yid', $kkId);
                    $stmt->execute();
                    $kkDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Find KK Yuvak
                    if ($kkDetails['kk_level'] == 1) {

                        //Find Not Send yuvak in Yuvak Table  ----  Without KKDI             ---
                        $str = "SELECT *  FROM contact c,yuvak y where  c." . $value['Field'] . "=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level) AND c.yid=y.yid) Order by y.name";
                        $sql = $str;
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                        $stmt->execute();
                        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        //Find Not Send yuvak in Yuvak  ----  With KKID
                        $str = "SELECT *  FROM contact c,yuvak y where  c." . $value['Field'] . "=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level AND tid=:tid AND kk_id=:kkid) AND c.yid=y.yid)  Order by y.name";
                        $sql = $str;
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                        $stmt->bindValue(':kkid', $kkDetails['yid']);
                        $stmt->bindValue(':tid', $kkDetails['tid']);
                        $stmt->execute();
                        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    //print("\n\n".$kkId."\n\n");

                    //Insert Notification table for today get
                    $notiYuvak = $this->insertNoti(3, $kkId);

                    //Max Yuvak Send Check
                    if (count($row) < 3) {

                        $j = $this->sendRandomYuvakNoti($firebase, count($row), $value, $row, $notiYuvak, $kkId, 0);

                        $newCount = $maxYuvak - count($row);

                        $sql = " UPDATE contact SET " . $value['Field'] . "=0";
                        $q = $sql;
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute();


                        if ($kkDetails['kk_level'] == 1) {

                            //Find Not Send yuvak in Yuvak Table  ----  Without KKDI       
                            $str = "SELECT *  FROM contact c,yuvak y where  c." . $value['Field'] . "=0 AND (c.yid IN (SELECT yid  FROM yuvak where kk_level>:kk_level) AND c.yid=y.yid) Order by y.name";
                            $sql = $str;
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                            $stmt->execute();
                            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } else {
                            //Find Not Send yuvak in Yuvak  ----  With KKID
                            $str = "SELECT * FROM contact c,yuvak y where c." . $value['Field'] . "=0 AND (c.yid IN (SELECT yid FROM yuvak where kk_level>:kk_level AND tid=:tid ) AND c.yid=y.yid) Order by y.name";
                            // AND kk_id=:kkid
                            $sql = $str;
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bindValue(':kk_level', $kkDetails['kk_level']);
                            // $stmt->bindValue(':kkid', $kkDetails['yid']);
                            $stmt->bindValue(':tid', $kkDetails['tid']);
                            $stmt->execute();
                            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        }




                        $this->sendRandomYuvakNoti($firebase, $newCount, $value, $row, $notiYuvak, $kkId, $j);


                        //print("yes");

                    } else {
                        $this->sendRandomYuvakNoti($firebase, $maxYuvak, $value, $row, $notiYuvak, $kkId, 0);
                    }
                }
            }
            return $row;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function sendRandomYuvakNoti($firebase, $count, $value, $row, $notiYuvak, $kkId, $temp)
    {
        $j = 0;

        for ($i = 0; $i < $count; $i++) {

            //Update Contact Table For Sending
            $sql = " UPDATE contact SET " . $value['Field'] . "=1 where  cid=:cid";
            $q = $sql;
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':cid', $row[$i]['cid']);
            $stmt->execute();


            //Update Send Notification Table For Yuvak
            $sql = " UPDATE notification SET yid=:yid where nid=:nid";
            $q = $sql;
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':yid', $row[$i]['yid']);
            $stmt->bindValue(':nid', $notiYuvak[$i + $temp]['nid']);
            $stmt->execute();

            //Yuvak KK Find
            $sql = "Select * from yuvak y,teams t where y.yid=:kk_id AND y.tid=t.tid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':kk_id', $row[$i]['kk_id']);
            $stmt->execute();
            $yuvakKK = $stmt->fetch(PDO::FETCH_ASSOC);

            $name = $row[$i]['name'] . " " . $row[$i]['surname'];
            $kkname = $yuvakKK['name'] . " " . $yuvakKK['surname'];
            $tname = $yuvakKK['tname'];
            $input['title'] = "$name";
            $input['subtitle'] = "$kkname\n$tname";
            $input['topic'] = $kkId;

            $this->sendAllNotification($firebase, $input);
            $j++;
        }
        return $j;
    }

    public function insertNoti($count, $kkId)
    {
        $sql = "Select * from notification where kk_id=:kk_id AND isRandom=1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kk_id', $kkId);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);


        if (count($row) >= $count) {
            return $row;
        } else {
            for ($i = 0; $i < ($count - count($row)); $i++) {
                $sql = " INSERT INTO notification SET yid=0,kk_id=:kk_id,isRandom=1";
                $q = $sql;
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':kk_id', $kkId);
                $stmt->execute();
            }

            $sql = "Select * from notification where kk_id=:kk_id AND isRandom=1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':kk_id', $kkId);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $row;
        }
    }


    public function getAltersByKK($input)
    {
        try {
            $qu = "SELECT * FROM notification n,yuvak y,teams t where y.tid = t.tid AND n.kk_id=:kk_id AND n.isRandom=1 AND n.yid=y.yid Order by y.name";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':kk_id', $input['kk_id']);
            $stmt->execute();
            $yuvakDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($yuvakDetails as $key => $value) {
                $qu = "SELECT * FROM yuvak y where yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['kk_id']);
                $stmt->execute();
                $kkDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                $yuvakDetails[$key]['kk'] = $kkDetails;
            }


            $qu = "SELECT * FROM notification n,yuvak y,teams t where y.tid = t.tid AND n.kk_id=:kk_id AND n.isRandom=1 AND n.yid=y.yid Order by n.cdt desc";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':kk_id', $input['kk_id']);
            $stmt->execute();
            $otherNotiDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //$yuvakDetails['alters'] = $otherNotiDetails;

            //print_r($kkDetails);
            return $yuvakDetails;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }


    public function getAllAltersByKK($input)
    {
        try {


            $qu = "SELECT * FROM notification n,yuvak y,teams t where y.tid = t.tid AND n.kk_id=:kk_id  AND n.yid=y.yid AND isRandom=0 AND (MONTH(y.dob)<MONTH(CURRENT_TIMESTAMP)  OR (DAY(y.dob)<=DAY(CURRENT_TIMESTAMP) AND MONTH(y.dob)=MONTH(CURRENT_TIMESTAMP))) Order by n.cdt desc limit 10";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':kk_id', $input['kk_id']);
            $stmt->execute();
            $otherNotiDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $otherNotiDetails;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function getUpcomingBday($input)
    {
        try {
            $kkId = $input['kk_id'];
            $kkLevel = $input['kk_level'];

            date_default_timezone_set("Asia/Kolkata");
            //$d=strtotime("now");
            //print($d);
            // $date = date("m-d", $d);

            if ($kkLevel == 1) {
                $kkLevel = $input['kk_level'] + 1;
                $qu = "SELECT y.*,t.*,
                        CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob)) as newDate,
                        DATE_FORMAT(DATE(CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob))),'%d-%m-%Y') as newDob
                     	
                    FROM yuvak y,teams t 
                    where y.tid = t.tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);

                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($input['kk_level'] == 2) {
                $kkLevel = $input['kk_level'] + 1;
                $tid = $input['tid'];
                $kk_id = $input['kk_id'];

                $qu = "SELECT y.*,t.*,
                        CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob)) as newDate,
                        DATE_FORMAT(DATE(CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob))),'%d-%m-%Y') as newDob
                    FROM yuvak y,teams t 
                    where y.tid = t.tid  AND y.tid =:tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10";

                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                //$stmt->bindValue(':kk_id', $kk_id); AND y.kk_id=:kk_id
                $stmt->bindValue(':tid', $tid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else if ($input['kk_level'] == 3) {
                $kkLevel = $input['kk_level'] + 1;
                $tid = $input['tid'];
                $kk_id = $input['kk_id'];

                $qu = "SELECT y.*,t.*,
                        CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob)) as newDate,
                        DATE_FORMAT(DATE(CONCAT(CASE WHEN (MONTH(dob)>MONTH(CURRENT_TIMESTAMP)  OR (DAY(dob)>DAY(CURRENT_TIMESTAMP) AND MONTH(dob)=MONTH(CURRENT_TIMESTAMP)))=1 THEN
                    		YEAR(CURRENT_TIMESTAMP)
                     	ELSE
                     		YEAR(CURRENT_TIMESTAMP)+1
                     	END,'-', MONTH(dob),'-', DAY(dob))),'%d-%m-%Y') as newDob
                    FROM yuvak y,teams t 
                    where y.tid = t.tid AND y.kk_id=:kk_id AND y.tid =:tid AND y.kk_level >=:kk_level Order BY DATE(newDate) ASC LIMIT 10";

                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                $stmt->bindValue(':kk_id', $kk_id);
                $stmt->bindValue(':tid', $tid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            foreach ($row as $key => $value) {
                $qu = "SELECT * FROM yuvak y where y.yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['kk_id']);
                $stmt->execute();
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                $row[$key]['kk'] = $r;
            }
            //  $row = [];
            return $row;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function addNewTaskByYuvak($task)
    {
        try {

            $sql = " INSERT INTO task SET yid=:yid, lastdate=:lastdate, taskname=:title";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':yid', $task['yid']);
            $stmt->bindValue(':lastdate', $task['lastdate']);
            $stmt->bindValue(':title', $task['title']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {
            print($e);
            return false;
        }
    }


    public function updateTaskByYuvak($task)
    {

        // print($task['yid']);
        // print($task['lastdate']);
        // print($task['title']);
        // print($task['isComp']);
        try {
            $sql = " UPDATE task SET isCompleted=:isCompleted, lastdate=:lastdate, taskname=:taskname where taskid=:taskid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':taskid', $task['taskid']);
            $stmt->bindValue(':lastdate', $task['lastdate']);
            $stmt->bindValue(':taskname', $task['title']);
            $stmt->bindValue(':isCompleted', $task['isComp']);
            $stmt->execute();

            return true;
        } catch (Exception $e) {

            return false;
        }
    }


    public function getAllTaskByYuvak($input)
    {
        try {

            $qu = "SELECT * FROM task where yid=:yid order by cdt desc";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':yid', $input['yid']);
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tasks as $key => $value) {

                $qu = "SELECT * FROM remarks where taskid=:taskid order by cdt desc";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':taskid', $value['taskid']);
                $stmt->execute();
                $tasksRemarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $tasks[$key]['taskRemark'] = $tasksRemarks;
            }

            //   $tasks = [];
            return $tasks;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function getAllTaskByKK($input)
    {
        $kkId = $input['kk_id'];
        $kkLevel = $input['kk_level'];
        $kkTid = $input['tid'];
        try {



            if ($kkLevel == 1) {
                $kkLevel = $kkLevel + 1;
                $qu = "SELECT * FROM yuvak y,teams t where y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                $stmt->execute();
                $yuvak = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $kkLevel = $kkLevel + 1;
                $qu = "SELECT * FROM yuvak y,teams t where y.tid = t.tid  AND y.kk_level >=:kk_level AND t.tid=:tid order by y.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                $stmt->bindValue(':tid', $kkTid);
                $stmt->execute();
                $yuvak = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $newArray = [];

            //Finde All Sub yuvak Task
            foreach ($yuvak as $keyy => $val) {
                $qu = "SELECT * FROM task where yid=:yid   order by cdt desc";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $val['yid']);
                $stmt->execute();
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($tasks) != 0) {
                    foreach ($tasks as $key => $value) {
                        array_push($newArray, $value);
                    }
                }
            }

            //Finde All Remarker for each task
            foreach ($newArray as $key => $value) {
                $qu = "SELECT * FROM remarks where taskid=:taskid order by cdt desc";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':taskid', $value['taskid']);
                $stmt->execute();
                $re = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $newArray[$key]['kkRemarks'] = $re;
            }


            //Finde All Yuvak Details
            foreach ($newArray as $key => $value) {
                $qu = "SELECT * FROM yuvak y,teams t where y.yid=:yid AND t.tid=y.tid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $value['yid']);
                $stmt->execute();
                $yuvak = $stmt->fetch(PDO::FETCH_ASSOC);
                $newArray[$key]['subYuvak'] = $yuvak;
            }

            return $newArray;
        } catch (Exception $e) {
            print($e);
            $row = [];
            return $row;
        }
    }

    public function yuvakXlReport($input)
    {
        try {
            $kkId = $input['kk_id'];
            $kkLevel = $input['kk_level'];

            if ($kkLevel == 1) {
                $kkLevel = $input['kk_level'] + 1;
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);

                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tid = $input['tid'];
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                //$stmt->bindValue(':dob', $date);
                $stmt->bindValue(':tid', $tid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $spreadsheet = new Spreadsheet();

            $columnArr = ['A1' => "No.", 'B1' => "Team", 'C1' => "KK Name", 'D1' => "KK Surname", 'E1' => "Name", 'F1' => "Middle Name", 'G1' => "Surname", 'H1' => "DOB", 'I1' => "Mobile", 'J1' => "Edu ", 'K1' => "Address"];
            $activeWorksheet = $spreadsheet->getActiveSheet();



            foreach ($columnArr as $k => $v) {
                $activeWorksheet->setCellValue($k, $v);
                $activeWorksheet->getStyle($k)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            $column = $this->getSabhaByLevel1($this->level_1id);

            $highestRow = $activeWorksheet->getHighestRow();
            $highestColumn = $activeWorksheet->getHighestColumn();
            $lastColumn = ++$highestColumn;
            for ($i = 1; $i < (count($column['colA']) * 2); $i++, $highestColumn++);
            $lastNewColumn = $highestColumn;


            foreach ($row as $key => $val) {
                $qu = "SELECT y.yid,y.aid,CONCAT(a.address,',',a.city,',',a.state,',',a.country) home FROM yuvak y, address a where a.aid=y.aid AND y.yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $val['yid']);
                $stmt->execute();
                $homeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                //Sabha Details
                $row = $key + 3;
                $lastC = $lastColumn;
                $activeWorksheet->setCellValue('A' . $row, $key + 1);
                $activeWorksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('B' . $row, $val['tname']);
                $activeWorksheet->setCellValue('C' . $row, $val['kkname']);
                $activeWorksheet->setCellValue('D' . $row, $val['kksurname']);
                $activeWorksheet->setCellValue('E' . $row, $val['name']);
                $activeWorksheet->setCellValue('F' . $row, $val['fname']);
                $activeWorksheet->setCellValue('G' . $row, $val['surname']);
                $activeWorksheet->setCellValue('H' . $row, $val['dob']);
                $activeWorksheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('I' . $row, $val['mobile']);
                $activeWorksheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('J' . $row, $val['ename']);
                $activeWorksheet->setCellValue('K' . $row, $homeDetails['home']);

                foreach ($column['colA'] as $k) {

                    $qu = "SELECT " . $k . " as p from attendance where yid=:yid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':yid', $val['yid']);
                    $stmt->execute();
                    $att = $stmt->fetch(PDO::FETCH_ASSOC);

                    $qu = "SELECT * from sabha where sid=:sid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':sid', ltrim($k, "s"));
                    $stmt->execute();
                    $sabha = $stmt->fetch(PDO::FETCH_ASSOC);

                    $cellMerge = $lastC;
                    $activeWorksheet->setCellValue($lastC . "2", "Att");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $activeWorksheet->setCellValue($lastC . $row, $att['p']);
                    $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                    $lastC++;
                    $spreadsheet->getActiveSheet()->mergeCells("$cellMerge" . "1" . ":$lastC" . "1");
                    $activeWorksheet->setCellValue($cellMerge . "1", strtoupper($k) . " | " . $sabha['date']);
                    $activeWorksheet->getStyle($cellMerge . "1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $activeWorksheet->setCellValue($lastC . "2", "Remark");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    if ($att['p'] == 2) {

                        $qu = "SELECT * From remarks where sid=:sid and rel=2 and yid=:yid";
                        $stmt = $this->conn->prepare($qu);
                        $stmt->bindValue(':sid', trim($k, 's'));
                        $stmt->bindValue(':yid', $val['yid']);
                        $stmt->execute();
                        $re = $stmt->fetch(PDO::FETCH_ASSOC);
                        $activeWorksheet->setCellValue($lastC . $row, $re['remark']);
                        $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $lastC++;
                    } else {
                        $lastC++;
                    }
                }
            }

            foreach ($activeWorksheet->getColumnIterator() as $column) {
                $activeWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $fileName = "../report/" . $kkId . ".xlsx";
            $writer->save($fileName);
            $out['status'] = true;
            $out['url'] = "https://paramitsolution.com/avdyuvak/report/" . $kkId . ".xlsx";
            return $out;
        } catch (Exception $e) {
            print($e);
            $out['status'] = false;
            $out['url'] = "";
            return $out;
        }
    }


    public function sendBdayNotificationToKK($firebase, $input)
    {
        $kkid  = $input['new_kk_id'];
        $nid = $input['nid'];
        $yid = $input['yid'];


        //Find Yuvak Details
        $qu = "SELECT name,surname,dob FROM yuvak where yid=:yid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $yid);
        $stmt->execute();
        $yuvak = $stmt->fetch(PDO::FETCH_ASSOC);

        $qu = "SELECT y.name,y.surname,t.tname,y.kk_level FROM yuvak y,teams t where yid=:yid AND y.tid=t.tid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $kkid);
        $stmt->execute();
        $kk = $stmt->fetch(PDO::FETCH_ASSOC);



        $name = $yuvak['name'] . " " . $yuvak['surname'];
        $kkname = $kk['name'] . " " . $kk['surname'];
        $tname = $kk['tname'];
        $input['title'] = "$name";
        $input['subtitle'] = "Birthday : " . $yuvak['dob'] . "\n$kkname\n$tname";
        $input['topic'] = $kkid;


        $sql = " INSERT INTO notification SET 
			            yid=:yid, kk_id=:kk_id, isForward=:isForward";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':yid', $yid);
        $stmt->bindValue(':kk_id', $kkid);
        if ($kk['kk_level'] == 1) {
            $stmt->bindValue(':isForward', 1);
        } else {
            $stmt->bindValue(':isForward', 0);
        }

        $stmt->execute();

        $sql = " UPDATE notification SET isForward=:isForward where nid=:nid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nid', $nid);
        $stmt->bindValue(':isForward', 1);
        $stmt->execute();

        $this->sendAllNotification($firebase, $input);

        //print_r($input);
    }




    public function yuvakSabhaReport($input)
    {
        try {
            $kkId = $input['kk_id'];
            $kkLevel = $input['kk_level'];
            $sid = $input['sid'];

            if ($kkLevel == 1) {
                $kkLevel = $input['kk_level'] + 1;
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);

                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tid = $input['tid'];
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                $stmt->bindValue(':tid', $tid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $spreadsheet = new Spreadsheet();

            $columnArr = ['A1' => "No.", 'B1' => "Team", 'C1' => "KK Name", 'D1' => "KK Surname", 'E1' => "Name", 'F1' => "Middle Name", 'G1' => "Surname", 'H1' => "DOB", 'I1' => "Mobile", 'J1' => "Edu "];
            $activeWorksheet = $spreadsheet->getActiveSheet();



            foreach ($columnArr as $k => $v) {
                $activeWorksheet->setCellValue($k, $v);
                $activeWorksheet->getStyle($k)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            $column = $this->getSabhaByLevel1($this->level_1id);
            $temp = 's' . $sid;
            $column['colA'] = [$temp];
            $highestRow = $activeWorksheet->getHighestRow();
            $highestColumn = $activeWorksheet->getHighestColumn();
            $lastColumn = ++$highestColumn;
            for ($i = 1; $i < (count($column['colA']) * 2); $i++, $highestColumn++);
            $lastNewColumn = $highestColumn;


            foreach ($row as $key => $val) {
                $row = $key + 3;
                $lastC = $lastColumn;
                $activeWorksheet->setCellValue('A' . $row, $key + 1);
                $activeWorksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('B' . $row, $val['tname']);
                $activeWorksheet->setCellValue('C' . $row, $val['kkname']);
                $activeWorksheet->setCellValue('D' . $row, $val['kksurname']);
                $activeWorksheet->setCellValue('E' . $row, $val['name']);
                $activeWorksheet->setCellValue('F' . $row, $val['fname']);
                $activeWorksheet->setCellValue('G' . $row, $val['surname']);
                $activeWorksheet->setCellValue('H' . $row, $val['dob']);
                $activeWorksheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('I' . $row, $val['mobile']);
                $activeWorksheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('J' . $row, $val['ename']);

                foreach ($column['colA'] as $k) {

                    $qu = "SELECT " . $k . " as p from attendance where yid=:yid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':yid', $val['yid']);
                    $stmt->execute();
                    $att = $stmt->fetch(PDO::FETCH_ASSOC);

                    $qu = "SELECT * from sabha where sid=:sid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':sid', ltrim($k, "s"));
                    $stmt->execute();
                    $sabha = $stmt->fetch(PDO::FETCH_ASSOC);

                    $cellMerge = $lastC;
                    $activeWorksheet->setCellValue($lastC . "2", "Att");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $activeWorksheet->setCellValue($lastC . $row, $att['p']);
                    $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                    $lastC++;
                    $spreadsheet->getActiveSheet()->mergeCells("$cellMerge" . "1" . ":$lastC" . "1");
                    $activeWorksheet->setCellValue($cellMerge . "1", strtoupper($k) . " | " . $sabha['date']);
                    $activeWorksheet->getStyle($cellMerge . "1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    $activeWorksheet->setCellValue($lastC . "2", "Remark");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                    if ($att['p'] == 2) {

                        $qu = "SELECT * From remarks where sid=:sid and rel=2 and yid=:yid";
                        $stmt = $this->conn->prepare($qu);
                        $stmt->bindValue(':sid', trim($k, 's'));
                        $stmt->bindValue(':yid', $val['yid']);
                        $stmt->execute();
                        $re = $stmt->fetch(PDO::FETCH_ASSOC);
                        $activeWorksheet->setCellValue($lastC . $row, $re['remark']);
                        $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $lastC++;
                    } else {
                        $lastC++;
                    }
                }
            }

            foreach ($activeWorksheet->getColumnIterator() as $column) {
                $activeWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $fileName = "../report/s" . $sid . ".xlsx";
            $writer->save($fileName);
            $out['status'] = true;
            $out['url'] = "https://paramitsolution.com/avdyuvak/report/s" . $sid . ".xlsx";

            // $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            // $spreadsheet2 = $reader->load($fileName);
            // $PDFwriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet2, 'Mpdf');    
            // $PDFwriter->setSheetIndex(0);   
            // $PDFwriter->save("../report/s1111.pdf");

            return $out;
        } catch (Exception $e) {
            print($e);
            $out['status'] = false;
            $out['url'] = "";
            return $out;
        }
    }


    public function insertDefaultMsg($input)
    {
        $kk_id = $input['kk_id'];
        $msg = $input['msg'];
        $type = $input['type'];
        try {
            $sql = "SELECT * from msg where kk_id=:kk_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':kk_id', $kk_id);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($row) == 0) {
                $sql = "INSERT INTO msg SET kk_id=:kk_id,message=:msg,type=:type";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':kk_id', $kk_id);
                $stmt->bindValue(':msg', $msg);
                $stmt->bindValue(':type', $type);
                $stmt->execute();
            } else {
                $sql = "Update msg SET message=:msg,type=:type where kk_id=:kk_id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':kk_id', $kk_id);
                $stmt->bindValue(':msg', $msg);
                $stmt->bindValue(':type', $type);
                $stmt->execute();
            }


            return true;
        } catch (Exception $e) {

            return false;
        }
    }

    public function defaultMsg($input)
    {
        $kk_id = $input['kk_id'];
        try {
            $sql = "SELECT * from msg where kk_id=:kk_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':kk_id', $kk_id);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $row[0]['mid'] = $row[0]['mid'] . "";
            $row[0]['kk_id'] = $row[0]['kk_id'] . "";
            $row[0]['type'] = $row[0]['type'] . "";
            return $row;
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }

    public function getPresentYuvak($input)
    {
        //Find KK Details
        $qu = "SELECT * FROM yuvak where yid=:yid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $input['yid']);
        $stmt->execute();
        $kk = $stmt->fetch(PDO::FETCH_ASSOC);



        $sid = $input['sid'];

        try {
            // if ($kk['kk_level'] == 1) {
                $sql = "SELECT y.*,kk.name as kname,kk.fname as kfname,kk.surname as ksname,t.tname, a.s" . $sid . " as st  FROM teams t, yuvak kk,yuvak y,attendance a where t.tid=y.tid AND kk.yid = y.kk_id AND y.yid=a.yid AND a.s" . $sid . " IN (1,2,3) order by t.tname";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($row as $k => $r) {
                    $arr = ['yid', 'aid', 'fid', 'mid', 'tid', 'kk_level', 'kk_id', 'isLogin', 'eid', 'isDeleted', 'st'];
                    foreach ($arr as $v) {
                        $row[$k][$v] = $row[$k][$v] . "";
                    }
                }

                return $row;
            // } else {
            //     $sql = "SELECT y.*,kk.name as kname,kk.fname as kfname,kk.surname as ksname,t.tname, a.s" . $sid . " as st  FROM teams t, yuvak kk,yuvak y,attendance a where  y.tid = " . $kk['tid'] . " AND t.tid=y.tid AND kk.yid = y.kk_id AND y.yid=a.yid AND a.s" . $sid . " IN (1,2,3) order by y.name";
            //     $stmt = $this->conn->prepare($sql);
            //     $stmt->execute();
            //     $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //     foreach ($row as $k => $r) {
            //         $arr = ['yid', 'aid', 'fid', 'mid', 'tid', 'kk_level', 'kk_id', 'isLogin', 'eid', 'isDeleted', 'st'];
            //         foreach ($arr as $v) {
            //             $row[$k][$v] = $row[$k][$v] . "";
            //         }
            //     }
            //     return $row;
            // }
        } catch (Exception $e) {
            $row = [];
            return $row;
        }
    }


    public function getPadhramniByLevel($kk_id)
    {


        //Find All Created Sabha by kk level 1
        $sql = "SELECT * FROM sabha s where s.kk_id=:kk_id AND isSabha=2";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':kk_id', $kk_id);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $newArr = [];
        $columnName = "(";
        foreach ($row as $k => $v) {
            if ($k === array_key_last($row)) {
                $columnName = $columnName . "COUNT(CASE WHEN s" . $v['sid'] . "=1 THEN 1 END)";
            } else {
                $columnName = $columnName . "COUNT(CASE WHEN s" . $v['sid'] . "=1 THEN 1 END)+";
            }

            array_push($newArr, "s" . $v['sid']);
        }
        $columnName .= ") as total";

        $out['col'] =  $columnName;
        $out['totalSabha'] =  count($row);
        $out['colA'] =  $newArr;
        return $out;
    }


    public function yuvakXlPadhramniReport($input)
    {
        try {
            $kkId = $input['kk_id'];
            $kkLevel = $input['kk_level'];

            if ($kkLevel == 1) {
                $kkLevel = $input['kk_level'] + 1;
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e  where e.eid = y.eid  AND y.kk_id = kk.yid AND y.tid = t.tid  AND y.kk_level >=:kk_level order by t.tname";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);

                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tid = $input['tid'];
                $qu = "SELECT y.*,kk.name kkname,kk.surname kksurname,t.tname,e.name ename FROM yuvak y,yuvak kk,teams t,education e where  e.eid = y.eid AND y.kk_id = kk.yid AND y.tid = t.tid AND y.tid=:tid AND y.kk_level >=:kk_level order by kk.name";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':kk_level', $kkLevel);
                //$stmt->bindValue(':dob', $date);
                $stmt->bindValue(':tid', $tid);
                $stmt->execute();
                $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $spreadsheet = new Spreadsheet();

            $columnArr = ['A1' => "No.", 'B1' => "Team", 'C1' => "KK Name", 'D1' => "KK Surname", 'E1' => "Name", 'F1' => "Middle Name", 'G1' => "Surname", 'H1' => "DOB", 'I1' => "Mobile", 'J1' => "Edu ", 'K1' => "Address"];
            $activeWorksheet = $spreadsheet->getActiveSheet();



            foreach ($columnArr as $k => $v) {
                $activeWorksheet->setCellValue($k, $v);
                $activeWorksheet->getStyle($k)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            // $column = $this->getPadhramniByLevel($this->level_1id);
            $column['colA'] = [$input['sid']];
            $highestRow = $activeWorksheet->getHighestRow();
            $highestColumn = $activeWorksheet->getHighestColumn();
            $lastColumn = ++$highestColumn;
            for ($i = 1; $i < (count($column['colA']) * 2); $i++, $highestColumn++);
            $lastNewColumn = $highestColumn;


            foreach ($row as $key => $val) {
                $qu = "SELECT y.yid,y.aid,CONCAT(a.address,',',a.city,',',a.state,',',a.country) home FROM yuvak y, address a where a.aid=y.aid AND y.yid=:yid";
                $stmt = $this->conn->prepare($qu);
                $stmt->bindValue(':yid', $val['yid']);
                $stmt->execute();
                $homeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

                //Sabha Details
                $row = $key + 3;
                $lastC = $lastColumn;
                $activeWorksheet->setCellValue('A' . $row, $key + 1);
                $activeWorksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('B' . $row, $val['tname']);
                $activeWorksheet->setCellValue('C' . $row, $val['kkname']);
                $activeWorksheet->setCellValue('D' . $row, $val['kksurname']);
                $activeWorksheet->setCellValue('E' . $row, $val['name']);
                $activeWorksheet->setCellValue('F' . $row, $val['fname']);
                $activeWorksheet->setCellValue('G' . $row, $val['surname']);
                $activeWorksheet->setCellValue('H' . $row, $val['dob']);
                $activeWorksheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('I' . $row, $val['mobile']);
                $activeWorksheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $activeWorksheet->setCellValue('J' . $row, $val['ename']);
                $activeWorksheet->setCellValue('K' . $row, $homeDetails['home']);

                foreach ($column['colA'] as $k) {

                    $qu = "SELECT " . $k . " as p from attendance where yid=:yid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':yid', $val['yid']);
                    $stmt->execute();
                    $att = $stmt->fetch(PDO::FETCH_ASSOC);




                    $qu = "SELECT * from sabha where sid=:sid";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->bindValue(':sid', ltrim($k, "s"));
                    $stmt->execute();
                    $sabha = $stmt->fetch(PDO::FETCH_ASSOC);

                    $cellMerge = $lastC;
                    $activeWorksheet->setCellValue($lastC . "2", "Att");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $activeWorksheet->setCellValue($lastC . $row, $att['p']);
                    $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                    $lastC++;
                    $spreadsheet->getActiveSheet()->mergeCells("$cellMerge" . "1" . ":$lastC" . "1");
                    $activeWorksheet->setCellValue($cellMerge . "1", $sabha['title'] . " | " . $sabha['date']);
                    $activeWorksheet->getStyle($cellMerge . "1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


                    $activeWorksheet->setCellValue($lastC . "2", "Remark");
                    $activeWorksheet->getStyle($lastC . "2")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    if ($att['p'] == 2) {

                        $qu = "SELECT * From remarks where sid=:sid and rel=2 and yid=:yid";
                        $stmt = $this->conn->prepare($qu);
                        $stmt->bindValue(':sid', trim($k, 's'));
                        $stmt->bindValue(':yid', $val['yid']);
                        $stmt->execute();
                        $re = $stmt->fetch(PDO::FETCH_ASSOC);
                        $activeWorksheet->setCellValue($lastC . $row, $re['remark']);
                        $activeWorksheet->getStyle($lastC . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $lastC++;
                    } else {
                        $lastC++;
                    }
                }
            }

            foreach ($activeWorksheet->getColumnIterator() as $column) {
                $activeWorksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }


            $filterRange = $spreadsheet->getActiveSheet()->calculateWorksheetDimension();
            $autoFilter = $spreadsheet->getActiveSheet()->setAutoFilter($filterRange);


            $writer = new Xlsx($spreadsheet);
            $fileName = "../report/" . $kkId . ".xlsx";
            $writer->save($fileName);
            $out['status'] = true;
            $out['url'] = "https://paramitsolution.com/avdyuvak/report/" . $kkId . ".xlsx";
            return $out;
        } catch (Exception $e) {
            print($e);
            $out['status'] = false;
            $out['url'] = "";
            return $out;
        }
    }

    // -----------------------------------------------------------------
    // Dashboard screens (member directory, real-time community
    // dashboard 1-4, sabha schedule events). 2026-05-28.
    // -----------------------------------------------------------------

    private function getKkScopeYids($kk_id)
    {
        $qu = "SELECT yid, kk_level, tid FROM yuvak WHERE yid=:yid";
        $stmt = $this->conn->prepare($qu);
        $stmt->bindValue(':yid', $kk_id);
        $stmt->execute();
        $kk = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$kk) {
            return array('yids' => array(), 'kk' => null);
        }

        if ($kk['kk_level'] == 1) {
            $qu = "SELECT yid FROM yuvak WHERE isDeleted=0 AND kk_level > :kk_level";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':kk_level', $kk['kk_level']);
        } else {
            $qu = "SELECT yid FROM yuvak WHERE isDeleted=0 AND tid=:tid AND kk_level > :kk_level";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':tid', $kk['tid']);
            $stmt->bindValue(':kk_level', $kk['kk_level']);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $yids = array();
        foreach ($rows as $r) {
            $yids[] = (int)$r['yid'];
        }
        return array('yids' => $yids, 'kk' => $kk);
    }

    public function getDashboardSummary($input)
    {
        try {
            $scope = $this->getKkScopeYids($input['kk_id']);
            $yids = $scope['yids'];
            if (empty($yids)) {
                return array('totalMembers' => 0, 'activeToday' => 0, 'pendingTasks' => 0, 'recentActivity' => array());
            }
            $in = implode(',', array_fill(0, count($yids), '?'));

            $qu = "SELECT COUNT(*) c FROM yuvak WHERE isDeleted=0 AND yid IN ($in)";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            $totalMembers = (int)$stmt->fetchColumn();

            $qu = "SELECT COUNT(DISTINCT yid) c FROM remarks WHERE DATE(cdt)=CURDATE() AND yid IN ($in)";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            $activeToday = (int)$stmt->fetchColumn();

            $qu = "SELECT COUNT(*) c FROM task WHERE isCompleted=0 AND yid IN ($in)";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            $pendingTasks = (int)$stmt->fetchColumn();

            $activity = array();

            $qu = "SELECT yid, name, surname, cdt FROM yuvak WHERE isDeleted=0 AND yid IN ($in) ORDER BY cdt DESC LIMIT 5";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $activity[] = array(
                    'type' => 'member_joined',
                    'icon' => 'user',
                    'title' => 'New Member Joined',
                    'subtitle' => trim($r['name'] . ' ' . $r['surname']) . ' joined',
                    'ref_id' => (int)$r['yid'],
                    'cdt' => $r['cdt']
                );
            }

            $qu = "SELECT sid, title, date, time, cdt FROM sabha WHERE isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak WHERE yid IN ($in) OR yid=?) ORDER BY cdt DESC LIMIT 5";
            $stmt = $this->conn->prepare($qu);
            $params = $yids;
            $params[] = $input['kk_id'];
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $activity[] = array(
                    'type' => 'sabha_scheduled',
                    'icon' => 'mic',
                    'title' => 'Sabha Scheduled',
                    'subtitle' => $r['title'],
                    'ref_id' => (int)$r['sid'],
                    'cdt' => $r['cdt']
                );
            }

            $qu = "SELECT t.taskid, t.taskname, t.isCompleted, t.cdt, y.name, y.surname FROM task t JOIN yuvak y ON y.yid=t.yid WHERE t.yid IN ($in) ORDER BY t.cdt DESC LIMIT 5";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $activity[] = array(
                    'type' => 'task_updated',
                    'icon' => 'task',
                    'title' => $r['isCompleted'] ? 'Task Completed' : 'Task Updated',
                    'subtitle' => trim($r['name'] . ' ' . $r['surname']) . ' - ' . $r['taskname'],
                    'ref_id' => (int)$r['taskid'],
                    'cdt' => $r['cdt']
                );
            }

            $qu = "SELECT yid, name, surname, dob FROM yuvak WHERE isDeleted=0 AND yid IN ($in) AND DATE_FORMAT(dob,'%m-%d')=DATE_FORMAT(CURDATE(),'%m-%d') LIMIT 5";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $activity[] = array(
                    'type' => 'birthday',
                    'icon' => 'cake',
                    'title' => 'Birthday Today',
                    'subtitle' => trim($r['name'] . ' ' . $r['surname']) . "'s birthday today",
                    'ref_id' => (int)$r['yid'],
                    'cdt' => date('Y-m-d 00:00:00')
                );
            }

            usort($activity, function ($a, $b) {
                return strcmp($b['cdt'], $a['cdt']);
            });
            $activity = array_slice($activity, 0, 15);

            return array(
                'totalMembers' => $totalMembers,
                'activeToday' => $activeToday,
                'pendingTasks' => $pendingTasks,
                'recentActivity' => $activity
            );
        } catch (Exception $e) {
            return array('totalMembers' => 0, 'activeToday' => 0, 'pendingTasks' => 0, 'recentActivity' => array());
        }
    }

    public function getAnalyticsDashboard($input)
    {
        try {
            $scope = $this->getKkScopeYids($input['kk_id']);
            $yids = $scope['yids'];
            if (empty($yids)) {
                return array('memberGrowth' => array(), 'sabhaAttendance' => 0, 'taskCompletion' => 0, 'leaderboard' => array());
            }
            $in = implode(',', array_fill(0, count($yids), '?'));

            $qu = "SELECT DATE(cdt) d, COUNT(*) c FROM yuvak WHERE isDeleted=0 AND cdt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND yid IN ($in) GROUP BY DATE(cdt) ORDER BY d";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $byDate = array();
            foreach ($rows as $r) {
                $byDate[$r['d']] = (int)$r['c'];
            }
            $running = (int)$this->conn->query("SELECT COUNT(*) FROM yuvak WHERE isDeleted=0 AND cdt < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND yid IN (" . implode(',', $yids) . ")")->fetchColumn();
            $growth = array();
            for ($i = 30; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                if (isset($byDate[$d])) {
                    $running += $byDate[$d];
                }
                $growth[] = array('date' => $d, 'total' => $running);
            }

            $qu = "SELECT sid FROM sabha WHERE isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak WHERE yid IN ($in) OR yid=?) ORDER BY date DESC LIMIT 10";
            $stmt = $this->conn->prepare($qu);
            $params = $yids;
            $params[] = $input['kk_id'];
            $stmt->execute($params);
            $sabhas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $sabhaAtt = 0;
            if (!empty($sabhas)) {
                $totP = 0;
                $totT = 0;
                foreach ($sabhas as $sid) {
                    $col = 's' . (int)$sid;
                    $qu = "SELECT COUNT(*) t, COUNT(CASE WHEN $col=1 THEN 1 END) p FROM attendance WHERE yid IN ($in)";
                    $stmt = $this->conn->prepare($qu);
                    $stmt->execute($yids);
                    $r = $stmt->fetch(PDO::FETCH_ASSOC);
                    $totT += (int)$r['t'];
                    $totP += (int)$r['p'];
                }
                $sabhaAtt = $totT > 0 ? round(($totP / $totT) * 100) : 0;
            }

            $qu = "SELECT COUNT(*) t, COUNT(CASE WHEN isCompleted=1 THEN 1 END) c FROM task WHERE yid IN ($in)";
            $stmt = $this->conn->prepare($qu);
            $stmt->execute($yids);
            $tr = $stmt->fetch(PDO::FETCH_ASSOC);
            $taskCompletion = ((int)$tr['t']) > 0 ? round(((int)$tr['c'] / (int)$tr['t']) * 100) : 0;

            $leaderboard = array();
            if (!empty($sabhas)) {
                $sumExpr = array();
                foreach ($sabhas as $sid) {
                    $sumExpr[] = "(CASE WHEN s" . (int)$sid . "=1 THEN 1 ELSE 0 END)";
                }
                $sumSql = implode(' + ', $sumExpr);
                $qu = "SELECT y.yid, y.name, y.surname, y.img, ($sumSql) score FROM yuvak y JOIN attendance a ON a.yid=y.yid WHERE y.isDeleted=0 AND y.yid IN ($in) ORDER BY score DESC, y.name LIMIT 10";
                $stmt = $this->conn->prepare($qu);
                $stmt->execute($yids);
                $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return array(
                'memberGrowth' => $growth,
                'sabhaAttendance' => $sabhaAtt,
                'taskCompletion' => $taskCompletion,
                'leaderboard' => $leaderboard
            );
        } catch (Exception $e) {
            return array('memberGrowth' => array(), 'sabhaAttendance' => 0, 'taskCompletion' => 0, 'leaderboard' => array());
        }
    }

    public function getDailyAgenda($input)
    {
        try {
            $date = isset($input['date']) && $input['date'] ? $input['date'] : date('Y-m-d');
            $scope = $this->getKkScopeYids($input['kk_id']);
            $yids = $scope['yids'];
            $timeline = array();

            if (!empty($yids)) {
                $in = implode(',', array_fill(0, count($yids), '?'));
                $qu = "SELECT sid, title, time FROM sabha WHERE isDeleted=0 AND date=? AND kk_id IN (SELECT yid FROM yuvak WHERE yid IN ($in) OR yid=?) ORDER BY time";
                $sParams = array_merge(array($date), $yids, array($input['kk_id']));
                $stmt = $this->conn->prepare($qu);
                $stmt->execute($sParams);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $timeline[] = array(
                        'type' => 'sabha',
                        'ref_id' => (int)$r['sid'],
                        'title' => $r['title'],
                        'time' => $r['time'],
                        'icon' => 'mic',
                        'canJoin' => 1
                    );
                }

                $qu = "SELECT t.taskid, t.taskname, t.lastdate, y.name, y.surname FROM task t JOIN yuvak y ON y.yid=t.yid WHERE t.isCompleted=0 AND t.lastdate=? AND t.yid IN ($in) ORDER BY t.cdt";
                $stmt = $this->conn->prepare($qu);
                $stmt->execute(array_merge(array($date), $yids));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $timeline[] = array(
                        'type' => 'follow_up',
                        'ref_id' => (int)$r['taskid'],
                        'title' => trim($r['name'] . ' ' . $r['surname']) . ' - ' . $r['taskname'],
                        'time' => '',
                        'icon' => 'user',
                        'canJoin' => 0
                    );
                }

                $qu = "SELECT title, event_time, rhid FROM resource_hub WHERE isDeleted=0 AND type='event' AND event_date=? ORDER BY event_time";
                $stmt = $this->conn->prepare($qu);
                $stmt->execute(array($date));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $timeline[] = array(
                        'type' => 'event',
                        'ref_id' => (int)$r['rhid'],
                        'title' => $r['title'],
                        'time' => $r['event_time'],
                        'icon' => 'calendar',
                        'canJoin' => 1
                    );
                }
            }

            $qu = "SELECT qnid, note, cdt FROM quick_notes WHERE isDeleted=0 AND yid=:yid AND ndate=:ndate ORDER BY cdt DESC";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':yid', $input['kk_id']);
            $stmt->bindValue(':ndate', $date);
            $stmt->execute();
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array('date' => $date, 'timeline' => $timeline, 'quickNotes' => $notes);
        } catch (Exception $e) {
            return array('date' => date('Y-m-d'), 'timeline' => array(), 'quickNotes' => array());
        }
    }

    public function addQuickNote($input)
    {
        try {
            $ndate = isset($input['ndate']) && $input['ndate'] ? $input['ndate'] : date('Y-m-d');
            $qu = "INSERT INTO quick_notes (yid, note, ndate) VALUES (:yid, :note, :ndate)";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':yid', $input['yid']);
            $stmt->bindValue(':note', $input['note']);
            $stmt->bindValue(':ndate', $ndate);
            $stmt->execute();
            return (int)$this->conn->lastInsertId();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function deleteQuickNote($input)
    {
        try {
            $qu = "UPDATE quick_notes SET isDeleted=1 WHERE qnid=:qnid";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':qnid', $input['qnid']);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getResourceHub($input)
    {
        try {
            $type = isset($input['type']) ? $input['type'] : '';
            $search = isset($input['search']) ? trim($input['search']) : '';

            $where = "isDeleted=0";
            $params = array();
            if ($type) {
                $where .= " AND type=:type";
                $params[':type'] = $type;
            }
            if ($search) {
                $where .= " AND (title LIKE :s OR description LIKE :s)";
                $params[':s'] = '%' . $search . '%';
            }

            $qu = "SELECT * FROM resource_hub WHERE $where ORDER BY CASE WHEN event_date IS NULL THEN 1 ELSE 0 END, event_date ASC, cdt DESC";
            $stmt = $this->conn->prepare($qu);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = array(
                'prayer_book' => array(),
                'meeting_minute' => array(),
                'event' => array(),
                'spotlight' => array(),
                'member_directory' => array()
            );
            foreach ($items as $it) {
                $grouped[$it['type']][] = $it;
            }
            return array('items' => $items, 'grouped' => $grouped);
        } catch (Exception $e) {
            return array('items' => array(), 'grouped' => array());
        }
    }

    public function addResource($input)
    {
        try {
            $qu = "INSERT INTO resource_hub (title, description, type, file_url, img, event_date, event_time, kk_id) VALUES (:title, :description, :type, :file_url, :img, :event_date, :event_time, :kk_id)";
            $stmt = $this->conn->prepare($qu);
            $stmt->bindValue(':title', $input['title']);
            $stmt->bindValue(':description', isset($input['description']) ? $input['description'] : null);
            $stmt->bindValue(':type', $input['type']);
            $stmt->bindValue(':file_url', isset($input['file_url']) ? $input['file_url'] : null);
            $stmt->bindValue(':img', isset($input['img']) ? $input['img'] : null);
            $stmt->bindValue(':event_date', isset($input['event_date']) && $input['event_date'] ? $input['event_date'] : null);
            $stmt->bindValue(':event_time', isset($input['event_time']) ? $input['event_time'] : null);
            $stmt->bindValue(':kk_id', isset($input['kk_id']) ? $input['kk_id'] : 0);
            $stmt->execute();
            return (int)$this->conn->lastInsertId();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getSabhaSchedule($input)
    {
        try {
            $scope = $this->getKkScopeYids($input['kk_id']);
            $yids = $scope['yids'];
            $search = isset($input['search']) ? trim($input['search']) : '';

            $in = empty($yids) ? '0' : implode(',', array_fill(0, count($yids), '?'));
            $params = $yids;
            $params[] = $input['kk_id'];

            $sql = "SELECT * FROM sabha WHERE isDeleted=0 AND kk_id IN (SELECT yid FROM yuvak WHERE yid IN ($in) OR yid=?)";
            if ($search) {
                $sql .= " AND (title LIKE ? OR date LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }
            $sql .= " ORDER BY date DESC, cdt DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $k => $r) {
                $col = 's' . (int)$r['sid'];
                $qu = "SELECT COUNT(*) t, COUNT(CASE WHEN $col=1 THEN 1 END) p, COUNT(CASE WHEN $col=2 THEN 1 END) ab FROM attendance";
                $st = $this->conn->prepare($qu);
                $st->execute();
                $a = $st->fetch(PDO::FETCH_ASSOC);
                $rows[$k]['total'] = (int)$a['t'];
                $rows[$k]['present'] = (int)$a['p'];
                $rows[$k]['absent'] = (int)$a['ab'];
            }
            return $rows;
        } catch (Exception $e) {
            return array();
        }
    }

    public function getMemberDirectory($input)
    {
        try {
            $scope = $this->getKkScopeYids($input['kk_id']);
            $yids = $scope['yids'];
            $search = isset($input['search']) ? trim($input['search']) : '';
            $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
            $offset = isset($input['offset']) ? (int)$input['offset'] : 0;

            if (empty($yids)) {
                return array('total' => 0, 'data' => array());
            }
            $in = implode(',', array_fill(0, count($yids), '?'));

            $params = $yids;
            $where = "y.isDeleted=0 AND y.yid IN ($in)";
            if ($search) {
                $where .= " AND (y.name LIKE ? OR y.surname LIKE ? OR y.mobile LIKE ? OR t.tname LIKE ?)";
                $s = '%' . $search . '%';
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
                $params[] = $s;
            }

            $countSql = "SELECT COUNT(*) FROM yuvak y LEFT JOIN teams t ON t.tid=y.tid WHERE $where";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();

            $sql = "SELECT y.yid, y.name, y.surname, y.mobile, y.img, y.type, t.tname AS team,
                        (SELECT COUNT(*) FROM remarks r WHERE r.yid=y.yid) AS remark_count,
                        (SELECT COUNT(*) FROM task tk WHERE tk.yid=y.yid) AS task_count
                    FROM yuvak y LEFT JOIN teams t ON t.tid=y.tid
                    WHERE $where ORDER BY y.name LIMIT $limit OFFSET $offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sabhas = $this->conn->query("SELECT sid FROM sabha WHERE isDeleted=0 ORDER BY date DESC LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($sabhas)) {
                $sumExpr = array();
                foreach ($sabhas as $sid) {
                    $sumExpr[] = "(CASE WHEN s" . (int)$sid . "=1 THEN 1 ELSE 0 END)";
                }
                $sumSql = implode(' + ', $sumExpr);
                foreach ($rows as $k => $r) {
                    $st = $this->conn->prepare("SELECT $sumSql AS sc FROM attendance WHERE yid=:yid");
                    $st->bindValue(':yid', $r['yid']);
                    $st->execute();
                    $rows[$k]['sabha_count'] = (int)$st->fetchColumn();
                }
            } else {
                foreach ($rows as $k => $r) {
                    $rows[$k]['sabha_count'] = 0;
                }
            }

            return array('total' => $total, 'data' => $rows);
        } catch (Exception $e) {
            return array('total' => 0, 'data' => array());
        }
    }
}
