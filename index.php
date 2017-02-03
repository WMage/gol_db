<?php
/**
 * Created by PhpStorm.
 * User: White Mage
 * Date: 2017.02.03.
 * Time: 15:34
 */
header("Content-Type: application/json;charset=utf-8");
define('DB_HOST', "localhost");
define('DB_USER', "root");
define('DB_PASS', "");
define('DB_NAME', "gol");
include "DB.php";
/** @var DB $DB */
$DB = new DB();
$data = json_decode(file_get_contents('php://input'), true);
if (isset($data["action"])) {
    switch ($data["action"]) {
        default:
        case "get":
            $name = (isset($data["name"])) ? (strtolower($data["name"])) : ("glinder");
            $data = $DB->query_result_to_array($DB->select("*", "data", "name='$name'"), true);
            if($data) {
                echo json_decode(html_entity_decode($data["generation"], ENT_QUOTES, "UTF-8"));
            }
            break;
        case "save":
            $name = (($data["name"] != "asd")) ? (strtolower($data["name"])) : ("asd" . rand(1, 1000000));
            $input_data = json_encode($data["data"]);
            $data = $DB->insert((array("name" => $name, "generation" => $input_data)), "data");
            if ($data) {
                echo "true";
            }
            break;
    }
}