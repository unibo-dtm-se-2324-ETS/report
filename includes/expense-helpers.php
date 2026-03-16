<?php
if (!function_exists('expense_currency_options')) {
  function expense_currency_options() {
    return array('USD', 'EUR', 'IQD', 'GBP', 'AED', 'SAR');
  }
}

if (!function_exists('expense_currency_symbol')) {
  function expense_currency_symbol($currency) {
    $symbols = array(
      'USD' => '$',
      'EUR' => 'EUR',
      'IQD' => 'IQD',
      'GBP' => 'GBP',
      'AED' => 'AED',
      'SAR' => 'SAR'
    );

    return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
  }
}

if (!function_exists('expense_money')) {
  function expense_money($amount, $currency) {
    return number_format((float)$amount, 2) . ' ' . expense_currency_symbol($currency);
  }
}

if (!function_exists('expense_h')) {
  function expense_h($value) {
    return htmlentities((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('expense_month_key')) {
  function expense_month_key($value = null) {
    $timestamp = $value ? strtotime($value) : time();

    return date('Y-m', $timestamp);
  }
}

if (!function_exists('expense_selected_currency')) {
  function expense_selected_currency($value, $default = 'USD') {
    $currency = strtoupper(trim((string)$value));
    $options = expense_currency_options();

    if (!in_array($currency, $options)) {
      $currency = $default;
    }

    return $currency;
  }
}

if (!function_exists('expense_csrf_token')) {
  function expense_csrf_token() {
    if (empty($_SESSION['expense_csrf_token'])) {
      $_SESSION['expense_csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['expense_csrf_token'];
  }
}

if (!function_exists('expense_verify_csrf')) {
  function expense_verify_csrf($token) {
    return isset($_SESSION['expense_csrf_token']) && is_string($token) && hash_equals($_SESSION['expense_csrf_token'], $token);
  }
}

if (!function_exists('expense_prepare_and_execute')) {
  function expense_prepare_and_execute($con, $sql, $types = '', $params = array()) {
    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
      return false;
    }

    if ($types !== '' && !empty($params)) {
      $bindParams = array($stmt, $types);
      foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
      }
      call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    }

    if (!mysqli_stmt_execute($stmt)) {
      mysqli_stmt_close($stmt);
      return false;
    }

    return $stmt;
  }
}

if (!function_exists('expense_fetch_all_assoc')) {
  function expense_fetch_all_assoc($stmt) {
    $rows = array();

    if (!$stmt) {
      return $rows;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
      }
      mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);
    return $rows;
  }
}

if (!function_exists('expense_fetch_one_assoc')) {
  function expense_fetch_one_assoc($stmt) {
    $row = null;

    if (!$stmt) {
      return $row;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
      $row = mysqli_fetch_assoc($result);
      mysqli_free_result($result);
    }

    mysqli_stmt_close($stmt);
    return $row;
  }
}

if (!function_exists('expense_close_statement')) {
  function expense_close_statement($stmt) {
    if ($stmt) {
      mysqli_stmt_close($stmt);
    }
  }
}

if (!function_exists('expense_ensure_schema')) {
  function expense_ensure_schema($con) {
    $currencyColumn = mysqli_query($con, "SHOW COLUMNS FROM tblexpense LIKE 'Currency'");
    if ($currencyColumn && mysqli_num_rows($currencyColumn) == 0) {
      mysqli_query($con, "ALTER TABLE tblexpense ADD COLUMN Currency varchar(10) NOT NULL DEFAULT 'USD' AFTER ExpenseCost");
    }

    $categoryColumn = mysqli_query($con, "SHOW COLUMNS FROM tblexpense LIKE 'CategoryId'");
    if ($categoryColumn && mysqli_num_rows($categoryColumn) == 0) {
      mysqli_query($con, "ALTER TABLE tblexpense ADD COLUMN CategoryId int(11) DEFAULT NULL AFTER Currency");
    }

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS tblitems (
      ID int(11) NOT NULL AUTO_INCREMENT,
      UserId int(11) NOT NULL,
      ItemName varchar(150) NOT NULL,
      CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (ID),
      KEY idx_userid (UserId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS tblcategories (
      ID int(11) NOT NULL AUTO_INCREMENT,
      UserId int(11) NOT NULL,
      CategoryName varchar(100) NOT NULL,
      CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (ID),
      KEY idx_userid (UserId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS tblbudgets (
      ID int(11) NOT NULL AUTO_INCREMENT,
      UserId int(11) NOT NULL,
      CategoryId int(11) NOT NULL,
      BudgetMonth char(7) NOT NULL,
      Currency varchar(10) NOT NULL DEFAULT 'USD',
      BudgetAmount decimal(12,2) NOT NULL DEFAULT 0.00,
      CreatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UpdatedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (ID),
      KEY idx_userid_month_currency (UserId, BudgetMonth, Currency),
      KEY idx_categoryid (CategoryId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
}

if (!function_exists('expense_ensure_user_categories')) {
  function expense_ensure_user_categories($con, $userid) {
    $checkStmt = expense_prepare_and_execute($con, "SELECT ID FROM tblcategories WHERE UserId=? LIMIT 1", 'i', array($userid));
    $existing = expense_fetch_one_assoc($checkStmt);

    if ($existing) {
      return;
    }

    $defaults = array('Food', 'Transport', 'Bills', 'Shopping', 'Health', 'Entertainment');
    $insertStmt = mysqli_prepare($con, "INSERT INTO tblcategories (UserId, CategoryName) VALUES (?, ?)");
    if (!$insertStmt) {
      return;
    }

    foreach ($defaults as $name) {
      mysqli_stmt_bind_param($insertStmt, 'is', $userid, $name);
      mysqli_stmt_execute($insertStmt);
    }

    mysqli_stmt_close($insertStmt);
  }
}

if (!function_exists('expense_get_categories')) {
  function expense_get_categories($con, $userid) {
    $stmt = expense_prepare_and_execute($con, "SELECT ID, CategoryName FROM tblcategories WHERE UserId=? ORDER BY CategoryName ASC", 'i', array($userid));
    return expense_fetch_all_assoc($stmt);
  }
}

if (!function_exists('expense_find_category_by_id')) {
  function expense_find_category_by_id($con, $userid, $categoryId) {
    $stmt = expense_prepare_and_execute($con, "SELECT ID, CategoryName FROM tblcategories WHERE ID=? AND UserId=? LIMIT 1", 'ii', array($categoryId, $userid));
    return expense_fetch_one_assoc($stmt);
  }
}

if (!function_exists('expense_budget_progress')) {
  function expense_budget_progress($spent, $budget) {
    if ((float)$budget <= 0) {
      return 0;
    }

    $progress = ((float)$spent / (float)$budget) * 100;

    return max(0, min(100, $progress));
  }
}
?>
