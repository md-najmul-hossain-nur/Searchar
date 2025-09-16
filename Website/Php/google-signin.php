<?php
session_start();
require_once '../Html/db.php';

$credential = $_POST['credential'] ?? '';
$selectedRole = $_POST['role'] ?? '';

$validRoles = ['user','police','volunteer','contributor'];
if(!$selectedRole || !in_array($selectedRole,$validRoles)){
    echo "<script>alert('Please select a valid role!'); window.location.href='../Html/login.html';</script>";
    exit;
}

if(!$credential){
    echo "<script>alert('Google sign in failed!'); window.location.href='../Html/login.html';</script>";
    exit;
}

$url = "https://oauth2.googleapis.com/tokeninfo?id_token=".$credential;
$response = @file_get_contents($url);
$data = json_decode($response,true);

if(!$data || empty($data['email'])){
    echo "<script>alert('Invalid Google token!'); window.location.href='../Html/login.html';</script>";
    exit;
}

$email = $data['email'];

$roleTableMap = [
    'user'=>['table'=>'users','id_col'=>'user_id','home'=>'../Html/User_Home.html'],
    'police'=>['table'=>'policemen','id_col'=>'police_id','home'=>'../Html/Policeman_Home.html'],
    'volunteer'=>['table'=>'volunteers','id_col'=>'volunteer_id','home'=>'../Html/Volunteer_Home.html'],
    'contributor'=>['table'=>'camera_contributors','id_col'=>'camera_id','home'=>'../Html/Camera_Contribution_Home.html']
];

$table = $roleTableMap[$selectedRole]['table'];
$id_col = $roleTableMap[$selectedRole]['id_col'];
$home_page = $roleTableMap[$selectedRole]['home'];

$stmt = $pdo->prepare("SELECT * FROM $table WHERE email=:email LIMIT 1");
$stmt->execute(['email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user){
    $_SESSION['user_id'] = $user[$id_col];
    $_SESSION['role'] = $selectedRole;
    header("Location: $home_page");
    exit;
}else{
    echo "<script>alert('No account found for $email. Please sign up first.'); window.location.href='../Html/login.html';</script>";
}
