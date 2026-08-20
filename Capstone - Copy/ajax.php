<?php

//print_r($_FILES);
//die;
session_start();
$action=$_REQUEST['action'];


if(!empty($action)){
    require_once 'partials/User_forms.php';
    $obj=new User_forms();
}

//adding user action
if($action=='addUserconcern' && !empty($_POST)){

// Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'error' => 'User is not logged in.'
        ]);
        exit();
    }
    // Get logged-in user's ID
    $userId = $_SESSION['user_id'];


    $concernType=$_POST['concern_type'];
    $title=$_POST['title'];
    $category=$_POST['category'];
    $priority=$_POST['priority'];
    $description=$_POST['description'];
    $evidence=$_FILES['evidence'] ?? null;

    $playerid=(!empty($_POST['userFormId']))? $_POST['userFormId']:  "";

    $evidencename="";
    // Upload evidence if there is a file
    if(!empty($evidence['name'])){
        $evidencename=$obj->uploadEvidence($evidence);
        // Data to insert into complaint_inquiry
    $playerData = [

        'user_id' => $userId,

        'concern_type' => $concernType,

        'title' => $title,

        'category_type' => $category,

        'priority_level' => $priority,

        'status' => 'Pending',

        'description' => $description,

        'filename' => $evidencename

    ];

    }else{
        
    $playerData = [

        'user_id' => $userId,

        'concern_type' => $concernType,

        'title' => $title,

        'category_type' => $category,

        'priority_level' => $priority,

        'status' => 'Pending',

        'description' => $description,

       

    ];

    }
    // Insert into database
        $playerid=$obj->add($playerData);
// Return inserted record
        if(!empty($playerid)){
            $player=$obj->getRow('id',$playerid);
            echo json_encode($player);
            exit();
        }
}
?>