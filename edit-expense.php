<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['detsuid'] == 0)) {
  header('location:logout.php');
} else {
  $userid = $_SESSION['detsuid'];
  $editid = intval($_GET['editid']);

  mysqli_query($con, "CREATE TABLE IF NOT EXISTS tblitems (
    ID int(11) NOT NULL AUTO_INCREMENT,
    UserId int(11) NOT NULL,
    ItemName varchar(150) NOT NULL,
    CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    KEY idx_userid (UserId)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  if (isset($_POST['submit'])) {
    $dateexpense = $_POST['dateexpense'];
    $item = $_POST['item'];
    $costitem = $_POST['costitem'];
    $query = mysqli_query($con, "update tblexpense set ExpenseDate='$dateexpense', ExpenseItem='$item', ExpenseCost='$costitem' where ID='$editid' and UserId='$userid'");
    if ($query) {
      echo "<script>alert('Expense has been updated');</script>";
      echo "<script>window.location.href='manage-expense.php'</script>";
    } else {
      $msg = "Something went wrong. Please try again";
    }
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daily Expense Tracker || Edit Expense</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/datepicker3.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
</head>
<body>
  <?php include_once('includes/header.php'); ?>
  <?php include_once('includes/sidebar.php'); ?>

  <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
    <div class="row">
      <ol class="breadcrumb">
        <li><a href="#"><em class="fa fa-home"></em></a></li>
        <li><a href="manage-expense.php">Expense</a></li>
        <li class="active">Edit Expense</li>
      </ol>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">Edit Expense</div>
          <div class="panel-body">
            <p style="font-size:16px; color:red" align="center"><?php if ($msg) { echo $msg; } ?></p>
            <div class="col-md-12">
              <?php
              $ret = mysqli_query($con, "select * from tblexpense where ID='$editid' and UserId='$userid'");
              $row = mysqli_fetch_array($ret);
              if ($row) {
              ?>
              <form role="form" method="post" action="">
                <div class="form-group">
                  <label>Date of Expense</label>
                  <input class="form-control" type="date" value="<?php echo $row['ExpenseDate']; ?>" name="dateexpense" required="true">
                </div>
                <div class="form-group">
                  <label>Item</label>
                  <select class="form-control" name="item" required="true">
                    <option value="<?php echo $row['ExpenseItem']; ?>"><?php echo $row['ExpenseItem']; ?></option>
                    <?php
                    $itemret = mysqli_query($con, "select ItemName from tblitems where UserId='$userid' order by ItemName asc");
                    while ($itemrow = mysqli_fetch_array($itemret)) {
                      if ($itemrow['ItemName'] == $row['ExpenseItem']) {
                        continue;
                      }
                    ?>
                    <option value="<?php echo $itemrow['ItemName']; ?>"><?php echo $itemrow['ItemName']; ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Cost of Item</label>
                  <input class="form-control" type="text" value="<?php echo $row['ExpenseCost']; ?>" required="true" name="costitem">
                </div>
                <div class="form-group has-success">
                  <button type="submit" class="btn btn-primary" name="submit">Update</button>
                  <a href="manage-expense.php" class="btn btn-default">Cancel</a>
                </div>
              </form>
              <?php } else { ?>
              <p align="center" style="color:red">Invalid expense record.</p>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
      <?php include_once('includes/footer.php'); ?>
    </div>
  </div>

  <script src="js/jquery-1.11.1.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/chart.min.js"></script>
  <script src="js/chart-data.js"></script>
  <script src="js/easypiechart.js"></script>
  <script src="js/easypiechart-data.js"></script>
  <script src="js/bootstrap-datepicker.js"></script>
  <script src="js/custom.js"></script>
</body>
</html>
<?php } ?>
