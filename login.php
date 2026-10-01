<?php

session_start();

include "demo.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $uname = $_POST["uname"];
    $password = $_POST["password"];


    $sql = "SELECT * FROM user_id WHERE uname = ? AND password = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $uname, $password);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    if (mysqli_num_rows($result) == 1) {

        $_SESSION["uname"] = $uname;

        echo "success";

    } else {

        echo "Invalid username or password.";

    }


    mysqli_stmt_close($stmt);

}

?>