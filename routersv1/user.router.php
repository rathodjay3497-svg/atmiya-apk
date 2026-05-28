<?php

use \Firebase\JWT\JWT;

$app->post('/login', function ($request, $response, $args) {
    $responseStatus = 200;
    $input = $request->getParsedBody();

    $responseArr = verifyRequiredParams($input, array('mobile', 'password'));

    if (count($responseArr) == 0) {
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->checkLogin($input['mobile'], $input['password']);
         
        if ($res['error'] == false) {
            $base62 = new Tuupola\Base62;
            $jti = $base62->encode($input['mobile']);
            $future = new DateTime($this->settings['jwt_expire_time']);
            $sub = array('mobile'=>$input['mobile']);
            $payload = [
                "iat" => time(),
                "jti" => $jti,
                "scope" => 'private',
                "exp" => $future->getTimeStamp(),
                "sub" => $sub
            ];

            $token = JWT::encode($payload, $this->jwt_secret, "HS256");

            $res['token'] = $token;
            $responseArr = $res;
    
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Invalid Mobile Number or Password.');
        }       
    }

    return $response->withJson($responseArr, $responseStatus,JSON_NUMERIC_CHECK);
});



$app->post('/changePassword', function ($request, $response, $args) {
    $responseStatus = 200;

     if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('yid','password'));
        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->changePassword($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/updateLogin', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid','status'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateLogin($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/getConditons', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getConditons();
        if (count($res) != 0) {
             $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }       
        
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/getYuvakByTeam', function ($request, $response, $args) {
    $responseStatus = 200;
    
    // if (is_jwt_valid())
    // {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_level','kk_id'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getYuvakByTeam($input);
           
           
            if (count($res) != 0) {
                
                $mainTotalRegular = 0;
                $mainTotalIrregular = 0;
                $mainTotalCount = count($res); 
                
                foreach($res as $k => $v){
                    $mainTotalCount += $v['totalSubYuvak'];
                    $mainTotalRegular += $v['subRegular'];
                    $mainTotalIrregular += $v['subIrregular'];
                    
                    if($v['sabhaSta'] == 0){
                        $mainTotalIrregular += 1;
                    }else if($v['sabhaSta'] == 1){
                        $mainTotalRegular += 1;
                    }
                }
                
                
                
                $responseArr['error'] = false;
                $responseArr['totalCount'] = $mainTotalCount;
                $responseArr['totalRegular'] = $mainTotalRegular;
                $responseArr['totalIrregular'] = $mainTotalIrregular;
                $responseArr['data'] = $res;
        
              
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    // } 
    // else {
    //     $responseStatus = 401;
    //     $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    // }
    
     
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/getYuvakByCategory', function ($request, $response, $args) {
    $responseStatus = 200;
    
    // if (is_jwt_valid())
    // {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_level','kk_id','type'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getYuvakByCategory($input);
           
           
            if (count($res) != 0) {
                
                $mainTotalRegular = 0;
                $mainTotalIrregular = 0;
                $mainTotalCount = count($res); 
                
                foreach($res as $k => $v){
                    $mainTotalCount += $v['totalSubYuvak'];
                    $mainTotalRegular += $v['subRegular'];
                    $mainTotalIrregular += $v['subIrregular'];
                    
                    if($v['sabhaSta'] == 0){
                        $mainTotalIrregular += 1;
                    }else if($v['sabhaSta'] == 1){
                        $mainTotalRegular += 1;
                    }
                }
                
                
                
                $responseArr['error'] = false;
                $responseArr['totalCount'] = $mainTotalCount;
                $responseArr['totalRegular'] = $mainTotalRegular;
                $responseArr['totalIrregular'] = $mainTotalIrregular;
                $responseArr['data'] = $res;
        
              
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    // } 
    // else {
    //     $responseStatus = 401;
    //     $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    // }
    
     
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getSearchYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_level','tid','query','type'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getSearchYuvak($input);
           
          // print(count($res));
            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else {
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/getEduOptions', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getEduOptions();
        if (count($res) != 0) {
             $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }       
        
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getYuvakCategoryOptions', function ($request, $response, $args) {
    $responseStatus = 200;
    
   
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getYuvakCategoryOptions();
        if (count($res) != 0) {
             $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }       
        
  
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getTeamsOption', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getTeamsOption();
        if (count($res) != 0) {
             $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }       
        
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/addYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('isImage','name',  'mobile', 'tid', 'kk_level', 'kk_id', 'eid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->addYuvak($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Added.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Added.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/updateYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('isImage','name', 'mobile', 'tid', 'kk_level', 'kk_id', 'eid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateYuvak($input);
           
            if ($res === true) {
             
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            elseif($res === 'mobile'){
               $responseArr = array('error' => true, 'message' => 'Other user used the same mobile number.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/addRemarks', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
       
        $responseArr = verifyRequiredParams($input, array('yid','rel'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->addRemarks($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Added.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Added.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/updateRemarks', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('rid','rel'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateRemarks($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});




$app->post('/getRemarkByYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getRemarkByYuvak($input);
            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['ddata'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','ddata' => []);
            }          
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});





$app->post('/getOnlyKK', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getOnlyKK($input['kk_level'],$input['kk_tid']);
        if (count($res) != 0) {
            $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }          
        
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/changeTeam', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('old_kk','new_kk'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->changeTeam($input);
            if ($res == true) {
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }             
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/updateLongLate', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid','longi','lat'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateLongLate($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/addSabha', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
       
        $responseArr = verifyRequiredParams($input, array('kk_id','title','date','time','isSabha'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->addSabha($this->settings,$input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Added.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Added.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/updateSabha', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('sid','title','date','time','isSabha'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateSabha($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/deleteYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid','isDelete'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->deleteYuvak($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getSabhaByKK', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
            
        $responseArr = verifyRequiredParams($input, array('kk_id'));
    
        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getSabhaByKK($input);
            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }          
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/getAttYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
            
        $responseArr = verifyRequiredParams($input, array('kk_level','tid','kk_id','sid'));
    
        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getAttYuvak($input);
            
            $responseArr['totalAtt'] = 0;
            $responseArr['P'] = 0;
            $responseArr['Ab'] = 0;
            $responseArr['Un'] = 0;
            if (count($res) != 0) {
                $responseArr['error'] = false;
                foreach($res as $key=>$value){
                    $responseArr['totalAtt'] += $value['total']+ $value['stotal'];
                    $responseArr['P'] += $value['P']+ $value['sP'];
                    $responseArr['Ab'] += $value['Ab']+$value['sAb'];
                    $responseArr['Un'] += $value['Un']+$value['sUn'];
                }
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }          
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/updateAtt', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('sid','yid','status'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateAtt($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


// $app->post('/sendAllNotification', function ($request, $response, $args) {
//     $responseStatus = 200;
    
//     if (is_jwt_valid())
//     {
//         $input = $request->getParsedBody();
        
//         $responseArr = verifyRequiredParams($input, array('title','subtitle','topic'));

//         if (count($responseArr) == 0) 
//         {
//             require_once MODELS_PATH . '/User.php';
//             $user = new User($this->db);
//             $res = $user->sendAllNotification($this->settings,$input);
//             if ($res == true) {
                 
//                 $responseArr = array('error' => false, 'message' => 'Notification was sent successfully.');
//             }
//             else{
//                 $responseArr = array('error' => true, 'message' => 'Notification was not sent successfully.');
//             }       
//         }
//     } else {
//         $responseStatus = 401;
//         $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
//     }
    
//      return $response->withJson($responseArr, $responseStatus);
// });


$app->post('/sendAllNotification', function ($request, $response, $args) {
    $responseStatus = 200;

    // Get the parsed body of the request
    $input = $request->getParsedBody();
    
    // Verify required parameters
    $responseArr = verifyRequiredParams($input, array('title', 'subtitle', 'topic'));

    if (count($responseArr) == 0) {
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        
        // Attempt to send the notification
        $res = $user->sendAllNotification($this->settings, $input);
        if ($res == true) {
            $responseArr = array('error' => false, 'message' => 'Notification was sent successfully.');
        } else {
            $responseArr = array('error' => true, 'message' => 'Notification was not sent successfully.');
        }
    } else {
        // Handle missing required parameters
        $responseArr = array('error' => true, 'message' => 'Missing required parameters.');
    }

    // Return the JSON response
    return $response->withJson($responseArr, $responseStatus);
});



$app->post('/sendSabhaNotification', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('title','kk_id'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->sendSabhaNotification($this->settings,$input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Notification was sent successfully.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Notification was not sent successfully.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->get('/sendBdayNotification', function ($request, $response, $args) {
    $responseStatus = 200;
    
    //if (is_jwt_valid())
    //{
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->sendBdayNotification($this->settings);
        if ($res == true) {
             
            $responseArr = array('error' => false, 'message' => 'Notification was sent successfully.');
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Notification was not sent successfully.');
        }       
        
    // } else {
    //     $responseStatus = 401;
    //     $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    // }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->get('/getRandomYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    // if (is_jwt_valid())
    // {
    
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getRandomYuvak($this->settings);
        if ($res == true) {
             
            $responseArr = array('error' => false, 'message' => 'Notification was sent successfully.');
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Notification was not sent successfully.');
        }       
        
    // } else {
    //     $responseStatus = 401;
    //     $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    // }
    
     return $response->withJson($responseArr, $responseStatus);
});




$app->post('/getAltersByKK', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $resa = $user->getAllAltersByKK($input);
            $res = $user->getAltersByKK($input);

            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
                $responseArr['alters'] = $resa; 
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/getUpcomingBday', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','kk_level'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->getUpcomingBday($input);

            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/addNewTaskByYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid','lastdate','title'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->addNewTaskByYuvak($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Added.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Added.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});





$app->post('/updateTaskByYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('taskid','lastdate','title','isComp'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->updateTaskByYuvak($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getAllTaskByYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('yid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->getAllTaskByYuvak($input);

            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/getAllTaskByKK', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','kk_level','tid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->getAllTaskByKK($input);

            if (count($res) != 0) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});




$app->post('/yuvakXlReport', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','kk_level'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->yuvakXlReport($input);
          
            if ($res['status'] == true) {
                $responseArr['error'] = false;
                $responseArr['downloadUrl'] = $res['url'];
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','downloadUrl' => "");
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/insertDbManual', function ($request, $response, $args) {
    $responseStatus = 200;
    
   
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('statid','endid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->insertDbManual($input);

        }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/sendBdayNotificationToKK', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('new_kk_id','nid','yid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->sendBdayNotificationToKK($this->settings,$input);
          
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Modified.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Modified.');
            }         
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});




$app->post('/yuvakSabhaReport', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','kk_level','sid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->yuvakSabhaReport($input);
          
            if ($res['status'] == true) {
                $responseArr['error'] = false;
                $responseArr['downloadUrl'] = $res['url'];
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','downloadUrl' => "");
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/insertDefaultMsg', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','type','msg'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->insertDefaultMsg($input);
            if ($res == true) {
                 
                $responseArr = array('error' => false, 'message' => 'Data Added.');
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Data Not Added.');
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/defaultMsg', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->defaultMsg($input);
          
            if ($res != []) {
                $responseArr['error'] = false;
                $responseArr['data'] = $res;
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.',);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});


$app->post('/getPresentYuvak', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('sid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->getPresentYuvak($input);
          
            if ($res != []) {
                $responseArr['error'] = false;
                $responseArr['total'] = count($res);
                $responseArr['doneP'] = 0;
                $responseArr['cancel'] = 0;
                foreach($res as $i){
                   
                    if($i['st'] == "2")
                        $responseArr['doneP']++;
                    else  if($i['st'] == "3")
                        $responseArr['cancel']++;
                } 
                $responseArr['remaining'] = $responseArr['total'] - ($responseArr['doneP']+$responseArr['cancel']);
                $responseArr['data'] = $res;
              
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.',);
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/yuvakXlPadhramniReport', function ($request, $response, $args) {
    $responseStatus = 200;
    
    if (is_jwt_valid())
    {
        $input = $request->getParsedBody();
        
        $responseArr = verifyRequiredParams($input, array('kk_id','sid'));

        if (count($responseArr) == 0) 
        {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
           
            $res = $user->yuvakXlPadhramniReport($input);
          
            if ($res['status'] == true) {
                $responseArr['error'] = false;
                $responseArr['downloadUrl'] = $res['url'];
            }
            else{
                $responseArr = array('error' => true, 'message' => 'Not Data Found.','downloadUrl' => "");
            }       
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    
     return $response->withJson($responseArr, $responseStatus);
});



$app->post('/getMentorTeams', function ($request, $response, $args) {
    $responseStatus = 200;
    
    // if ( true || is_jwt_valid() )
    // {
        $input = $request->getParsedBody();
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getMentorTeams($input['kkid']);
        if (count($res) != 0) {
             $responseArr['error'] = false;
            $responseArr['data'] = $res;
        }
        else{
            $responseArr = array('error' => true, 'message' => 'Not Data Found.','data' => []);
        }

    // }
    // else {
    //     $responseStatus = 401;
    //     $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    // }

     return $response->withJson($responseArr, $responseStatus);
});


// -----------------------------------------------------------------
// Dashboard screens (member directory, real-time community dashboard
// 1-4, sabha schedule events). 2026-05-28.
// -----------------------------------------------------------------

$app->post('/getDashboardSummary', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_id'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getDashboardSummary($input);
            $responseArr = array('error' => false, 'data' => $res);
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/getAnalyticsDashboard', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_id'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getAnalyticsDashboard($input);
            $responseArr = array('error' => false, 'data' => $res);
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/getDailyAgenda', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_id'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getDailyAgenda($input);
            $responseArr = array('error' => false, 'data' => $res);
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/addQuickNote', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('yid', 'note'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $qnid = $user->addQuickNote($input);
            if ($qnid) {
                $responseArr = array('error' => false, 'message' => 'Note added.', 'qnid' => $qnid);
            } else {
                $responseArr = array('error' => true, 'message' => 'Note not added.');
            }
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/deleteQuickNote', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('qnid'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $ok = $user->deleteQuickNote($input);
            $responseArr = array('error' => !$ok, 'message' => $ok ? 'Note deleted.' : 'Note not deleted.');
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus);
});

$app->post('/getResourceHub', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        if (!is_array($input)) $input = array();
        require_once MODELS_PATH . '/User.php';
        $user = new User($this->db);
        $res = $user->getResourceHub($input);
        $responseArr = array('error' => false, 'data' => $res);
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/addResource', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('title', 'type'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $rhid = $user->addResource($input);
            if ($rhid) {
                $responseArr = array('error' => false, 'message' => 'Resource added.', 'rhid' => $rhid);
            } else {
                $responseArr = array('error' => true, 'message' => 'Resource not added.');
            }
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/getSabhaSchedule', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_id'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getSabhaSchedule($input);
            $responseArr = array('error' => false, 'data' => $res);
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});

$app->post('/getMemberDirectory', function ($request, $response, $args) {
    $responseStatus = 200;
    if (is_jwt_valid()) {
        $input = $request->getParsedBody();
        $responseArr = verifyRequiredParams($input, array('kk_id'));
        if (count($responseArr) == 0) {
            require_once MODELS_PATH . '/User.php';
            $user = new User($this->db);
            $res = $user->getMemberDirectory($input);
            $responseArr = array('error' => false, 'data' => $res);
        }
    } else {
        $responseStatus = 401;
        $responseArr = array('error' => true, 'message' => 'Unauthorized accessed.');
    }
    return $response->withJson($responseArr, $responseStatus, JSON_NUMERIC_CHECK);
});
