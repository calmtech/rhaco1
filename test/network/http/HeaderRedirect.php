<?php
include_once '__init__.php';
Rhaco::import("network.http.Header");
Header::redirect(Rhaco::url("network/http/HeaderOutputB.php"));
?>