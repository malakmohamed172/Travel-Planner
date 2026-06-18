<?php

require_once __DIR__ . '/../Models/expense.php';
require_once __DIR__ . '/../Models/ExpenseSplit.php';
require_once __DIR__ . '/../Controllers/DBController.php';

$db = new DBController();
$db->openConnection();

$expense = new Expense($db->connection);


// ADD
if (isset($_POST['action']) && $_POST['action'] == 'add') {

    $expense->trip_id = $_POST['trip_id'];
    $expense->amount  = $_POST['amount'];

    $expense->create();

    $expense_id = $db->connection->insert_id;

    $split = new ExpenseSplit($db->connection);
    $split->split($expense_id, $_POST['trip_id'], $_POST['amount']);

    header("Location: ../Views/User/Expenses/list.php?trip_id=" . $_POST['trip_id']);
    exit;
}


// DELETE
if (isset($_GET['action']) && $_GET['action'] == 'delete') {

    $expense->expense_id = $_GET['id'];
    $expense->delete();

    header("Location: ../Views/User/Expenses/list.php?trip_id=" . $_GET['trip_id']);
    exit;
}