<?php

include "demo.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $admin_name = trim($_POST["admin_name"] ?? "");
    $uname = trim($_POST["uname"] ?? "");
    $password = trim($_POST["password"] ?? "");


    if ($admin_name == "") {
        echo "Admin Name is required.";
        exit();
    }

    if ($uname == "") {
        echo "Username is required.";
        exit();
    }

    if ($password == "") {
        echo "Password is required.";
        exit();
    }


    /* Check Username */

    $sql = "SELECT uname FROM user_id WHERE uname = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $uname);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    if (mysqli_num_rows($result) > 0) {

        echo "uname_exists";

        mysqli_stmt_close($stmt);

        exit();
    }

    mysqli_stmt_close($stmt);


    /* Generate Aid */

    do {

        $Aid = random_int(100000, 999999);

        $aid_check = "SELECT Aid FROM user_id WHERE Aid = ?";

        $aid_stmt = mysqli_prepare($conn, $aid_check);

        mysqli_stmt_bind_param($aid_stmt, "i", $Aid);

        mysqli_stmt_execute($aid_stmt);

        $aid_result = mysqli_stmt_get_result($aid_stmt);

        $exists = mysqli_num_rows($aid_result) > 0;

        mysqli_stmt_close($aid_stmt);

    } while ($exists);


    /* Insert */

    $sql = "INSERT INTO user_id
            (Aid, admin_name, uname, password)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "isss",
        $Aid,
        $admin_name,
        $uname,
        $password
    );


    if (mysqli_stmt_execute($stmt)) {

        echo "success";

    } else {

        echo "Database error: " . mysqli_error($conn);

    }


    mysqli_stmt_close($stmt);
}

?>