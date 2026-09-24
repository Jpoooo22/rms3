<?php

function validate_input_text($textValue){
    if (!empty($textValue)){
        $trim_text = trim($textValue);
        // remove illegal character
        $sanitize_str = filter_var($trim_text, FILTER_SANITIZE_STRING);
        return $sanitize_str;
    }
    return '';
}


function validate_input_number($textValue){
    if (!empty($textValue)){
        $trim_text = trim($textValue);
        // remove illegal character
        $sanitize_str = filter_var($trim_text, FILTER_SANITIZE_NUMBER_FLOAT);
        return $sanitize_str;
    }
    return '';
}


// profile image
function upload_profile($path, $file){
    $targetDir = $path;
    $default = "defaultprofilepic.png";

    // get the filename
    $filename = basename($file['name']);
    $targetFilePath = $targetDir . $filename;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    If(!empty($filename)){
        // allow certain file format
        $allowType = array('jpg', 'png', 'jpeg', 'gif');
        if(in_array($fileType, $allowType)){
            // upload file to the server
            if(move_uploaded_file($file['tmp_name'], $targetFilePath)){
                return $targetFilePath;
            }
        }
    }

    

    // return default image
    return $path . $default;
}


// report
function upload_file_report($path, $file){
    $targetDir = $path;

    // get the filename
    $filename = basename($file['name']);
    $targetFilePath = $targetDir . $filename;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    If(!empty($filename)){
        // allow certain file format
        $allowType = array('pdf', 'xlsx', 'xlsm', 'xlsb', 'xltx', 'docx', 'dot', 'dotx', 'pptx', 'pptm', 'ppt');
        if(in_array($fileType, $allowType)){
            // upload file to the server
            if(move_uploaded_file($file['tmp_name'], $targetFilePath)){
                return $targetFilePath;
            }
        }
    }
}

// get user info
 function get_user_info($con, $id){
    $query = "SELECT id, SerialID, rank, username, password, lastname, firstname, middlename, profilepic, email, phoneNo, usertype FROM users WHERE id=?";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 'i', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $row = mysqli_fetch_array($result);
    return empty($row) ? false : $row;
}

// get task info
 function get_task_info($con, $id){
    $query = "SELECT id_task, task_name, task_des, task_start_date, task_end_date, contributor, ref_file, added_date_time, added_By, task_status FROM task WHERE id_task=?";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 'i', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $row = mysqli_fetch_array($result);
    return empty($row) ? false : $row;
}

function get_user_todolist($con, $id){
    $query = "SELECT * from todolist WHERE userid=? ";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 's', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $todolistrow = mysqli_fetch_array($result);
    return empty($todolistrow) ? false : $todolistrow;
}


function get_user_login_action($con, $id){
    $query = "SELECT firstname FROM users WHERE id=?";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 'i', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $rows = mysqli_fetch_array($result);
    $action_name = "Successfully logged in.";
    $action_page = "Login page";
    $user_name = $rows['firstname'] ." ". $rows['lastname'];
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO log_activities (id, user_id, user_name, ip, action_name, action_page, action_date)";
    $query .= "VALUES(' ', ?, ?, ?, ?, ?, NOW())";
     // initialize a statement
     $q = mysqli_stmt_init($con);

     // prepare sql statement
     mysqli_stmt_prepare($q, $query);
 
     // bind values
     mysqli_stmt_bind_param($q, 'sssss', $id, $user_name, $ipaddress, $action_name, $action_page);
 
     // execute statement
     mysqli_stmt_execute($q);


   
 
}

function get_user_todolist_action($con, $id){
    $query = "SELECT firstname FROM users WHERE id=?";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 'i', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $rows = mysqli_fetch_array($result);
    $action_name = "Added new todo list.";
    $action_page = "todolist page";
    $user_name = $rows['firstname'];
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO log_activities (id, user_id, user_name, ip, action_name, action_page, action_date)";
    $query .= "VALUES(' ', ?, ?, ?, ?, ?, NOW())";
     // initialize a statement
     $q = mysqli_stmt_init($con);

     // prepare sql statement
     mysqli_stmt_prepare($q, $query);
 
     // bind values
     mysqli_stmt_bind_param($q, 'sssss', $id, $user_name, $ipaddress, $action_name, $action_page);
 
     // execute statement
     mysqli_stmt_execute($q);


   
 
}






function get_user_addnewuser_action($con, $id){
    $query = "SELECT firstname FROM users WHERE id=?";
    $q = mysqli_stmt_init($con);

    mysqli_stmt_prepare($q, $query);

    // bind the statement
    mysqli_stmt_bind_param($q, 'i', $id);

    // execute sql statement
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);

    $rows = mysqli_fetch_array($result);
    $action_name = "Added new user";
    $action_page = "new user page";
    $user_name = $rows['firstname'];
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    $query = "INSERT INTO log_activities (id, user_id, user_name, ip, action_name, action_page, action_date)";
    $query .= "VALUES(' ', ?, ?, ?, ?, ?, NOW())";
     // initialize a statement
     $q = mysqli_stmt_init($con);

     // prepare sql statement
     mysqli_stmt_prepare($q, $query);
 
     // bind values
     mysqli_stmt_bind_param($q, 'sssss', $id, $user_name, $ipaddress, $action_name, $action_page);
 
     // execute statement
     mysqli_stmt_execute($q);


   
 
}





