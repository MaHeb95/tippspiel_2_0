<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 07.09.17
 * Time: 12:52
 */

$dbConn = mysqli_connect($db_host, $db_user, $db_password, $db_name) or die('MySQL connect failed. ' . mysqli_connect_error());
function dbQuery($sql) {
    global $dbConn;
    $result = mysqli_query($dbConn, $sql) or die(mysqli_error($dbConn));
    return $result;
}
function dbAffectedRows() {
    global $dbConn;
    return mysqli_affected_rows($dbConn);
}
function dbFetchArray($result, $resultType = MYSQLI_NUM) {
    return mysqli_fetch_array($result, $resultType);
}
function dbFetchAssoc($result) {
    return mysqli_fetch_assoc($result);
}
function dbFetchRow($result) {
    return mysqli_fetch_row($result);
}
function dbFreeResult($result) {
    return mysqli_free_result($result);
}
function dbNumRows($result) {
    return mysqli_num_rows($result);
}
function dbNumFields($result) {
    return mysqli_num_fields($result);
}
function dbInsertId() {
    global $dbConn;
    return mysqli_insert_id($dbConn);
}
function closeConn() {
    global $dbConn;
    mysqli_close($dbConn);
}
