<?php
$app->add(new \Slim\Middleware\JwtAuthentication([
    "path" => ["/"],
    "passthrough" => ["/login", "/changePassword","/getYuvakByTeam","/getEduOptions","/getTeamsOption","/addYuvak","/updateYuvak","/addRemarks","/updateRemarks","/getRemarkByYuvak","/getOnlyKK","/changeTeam","/updateLongLate","/addSabha","/updateSabha","/getSabhaByKK","/getAttYuvak","/updateAtt","/sendAllNotification","/sendSabhaNotification","/sendBdayNotification","/getRandomYuvak","/getAltersByKK","/getUpcomingBday","/getConditons","/addNewTaskByYuvak","/updateTaskByYuvak","/getAllTaskByYuvak","/getAllTaskByKK","/yuvakXlReport","/updateLogin","/insertDbManual","/sendBdayNotificationToKK","/yuvakSabhaReport","/getSearchYuvak","/defaultMsg","/insertDefaultMsg","/getPresentYuvak","/yuvakXlPadhramniReport","/getMentorTeams","/getYuvakCategoryOptions","/getYuvakByCategory"],
    "secret" => $app->getContainer()['settings']['jwt_secret'],
    "secure" => false,
    "callback" => function ($request, $response, $arguments) use ($container) {
        $container["jwt"] = $arguments["decoded"];
    },
    "error" => function ($request, $response, $arguments) {
        $data["error"] = true;
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT');
});
