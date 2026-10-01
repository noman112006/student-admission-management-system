<?php

include "demo.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $uname = trim($_POST["uname"] ?? "");
    $new_password = trim($_POST["new_password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");

    /* Username */
    if ($uname == "") {
        $message = "Username is required.";
    }

    /* New Password */
    else if ($new_password == "") {
        $message = "New Password is required.";
    }

    /* Password exactly 6 characters */
    else if (strlen($new_password) != 6) {
        $message = "Password must be exactly 6 characters.";
    }

    /* Confirm Password */
    else if ($confirm_password == "") {
        $message = "Confirm Password is required.";
    }

    /* Password Match */
    else if ($new_password != $confirm_password) {
        $message = "Passwords do not match.";
    }

    else {

        /* Check Username */

        $sql = "SELECT password FROM user_id WHERE uname = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $message = "Database error.";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $uname);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 0) {

                $message = "Username not found.";

            } else {

                $row = mysqli_fetch_assoc($result);

                $old_password = $row["password"];

                /* Same password check */

                if ($new_password == $old_password) {

                    $message = "New password cannot be same as previous password.";

                } else {

                    /* Update Password */

                    $update_sql = "UPDATE user_id SET password = ? WHERE uname = ?";

                    $update_stmt = mysqli_prepare($conn, $update_sql);

                    if (!$update_stmt) {

                        $message = "Database error.";

                    } else {

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "ss",
                            $new_password,
                            $uname
                        );

                        if (mysqli_stmt_execute($update_stmt)) {

                            $message = "Password reset successfully.";
                            $success = true;

                        } else {

                            $message = "Password update failed.";
                        }

                        mysqli_stmt_close($update_stmt);
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Forgot Password</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eaf2fb, #d6e5f5);
        }

        .header {
            background: linear-gradient(90deg, #0b3c7c, #1559a6);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 26px;
            font-weight: bold;
        }

        .box {
            width: 400px;
            background: white;
            margin: 70px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        h2 {
            text-align: center;
            color: #0b3c7c;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .required {
            color: red;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #aaa;
            border-radius: 5px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #1559a6;
        }

        /* Red border when invalid */

        input.invalid {
            border: 2px solid red;
        }

        /* Error text */

        .error {
            color: red;
            font-size: 13px;
            margin-top: 4px;
            min-height: 16px;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background: #0b3c7c;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1559a6;
        }

        .message {
            text-align: center;
            margin-top: 15px;
            color: red;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #0b3c7c;
            text-decoration: none;
            font-weight: bold;
        }

    </style>

</head>

<body>

    <div class="header">
        Student Admission Management System
    </div>


    <div class="box">

        <h2>Forgot Password</h2>


        <form method="POST"
              action="forgot_password.php"
              onsubmit="return validateForm()">


            <!-- Username -->

            <label>
                Username <span class="required">*</span>
            </label>

            <input
                type="text"
                name="uname"
                id="uname"
                maxlength="100"
                value="<?php echo htmlspecialchars($_POST['uname'] ?? ''); ?>"
            >

            <div id="unameError" class="error"></div>


            <!-- New Password -->

            <label>
                New Password <span class="required">*</span>
            </label>

            <input
                type="password"
                name="new_password"
                id="new_password"
                maxlength="6"
            >

            <div id="passwordError" class="error"></div>


            <!-- Confirm Password -->

            <label>
                Confirm Password <span class="required">*</span>
            </label>

            <input
                type="password"
                name="confirm_password"
                id="confirm_password"
                maxlength="6"
            >

            <div id="confirmError" class="error"></div>


            <!-- Button -->

            <button type="submit">
                Reset Password
            </button>

        </form>


        <!-- PHP Message -->

        <?php if ($message != "") { ?>

            <div class="message <?php echo $success ? 'success' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <!-- Back -->

        <div class="back">
            <a href="login.html">← Back to Login</a>
        </div>

    </div>


<script>

function validateForm() {

    var uname = document.getElementById("uname");

    var password = document.getElementById("new_password");

    var confirmPassword =
        document.getElementById("confirm_password");


    var unameError =
        document.getElementById("unameError");

    var passwordError =
        document.getElementById("passwordError");

    var confirmError =
        document.getElementById("confirmError");


    var valid = true;


    /* Remove old errors */

    uname.classList.remove("invalid");

    password.classList.remove("invalid");

    confirmPassword.classList.remove("invalid");


    unameError.innerHTML = "";

    passwordError.innerHTML = "";

    confirmError.innerHTML = "";


    /* Username */

    if (uname.value.trim() == "") {

        uname.classList.add("invalid");

        unameError.innerHTML =
            "Username is required.";

        valid = false;
    }


    /* Password */

    if (password.value == "") {

        password.classList.add("invalid");

        passwordError.innerHTML =
            "New Password is required.";

        valid = false;

    }

    else if (password.value.length != 6) {

        password.classList.add("invalid");

        passwordError.innerHTML =
            "Password must be exactly 6 characters.";

        valid = false;
    }


    /* Confirm Password */

    if (confirmPassword.value == "") {

        confirmPassword.classList.add("invalid");

        confirmError.innerHTML =
            "Confirm Password is required.";

        valid = false;

    }

    else if (password.value != confirmPassword.value) {

        confirmPassword.classList.add("invalid");

        confirmError.innerHTML =
            "Passwords do not match.";

        valid = false;
    }


    return valid;
}


/* Username typing */

document.getElementById("uname").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("unameError").innerHTML = "";

    }
);


/* Password typing */

document.getElementById("new_password").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("passwordError").innerHTML = "";

    }
);


/* Confirm password typing */

document.getElementById("confirm_password").addEventListener(
    "input",
    function() {

        this.classList.remove("invalid");

        document.getElementById("confirmError").innerHTML = "";

    }
);

</script>


<?php

/* Redirect after successful password reset */

if ($success) {

    echo "<script>

        setTimeout(function() {

            window.location.href = 'login.html';

        }, 1500);

    </script>";
}

?>

</body>

</html>