<?php
//session start
session_start();

include '../db/db.php';

//pick session id
$customer_id = $_SESSION['user_id'];

$sql="select meal_id from meal where meal_id='$customer_id'";
$result = $conn->query($sql);

//result found show a button
if ($result->num_rows > 0) {
    echo "<a href='vpayment.php' class='btn btn-primary'>Deposit Ammount</a>";
}












?>