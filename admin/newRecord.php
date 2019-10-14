<?php
require_once('../ajax/system/db.php');
if(isset($_POST['newCompany'])){
    $db=getDB();
    $checkClient = $db->prepare("select cname from company where cname=:cname");
    $checkClient->bindParam('cname',$_POST['cname']);
    $checkClient->execute();
    if(count($checkClient->fetchAll(PDO::FETCH_ASSOC))>0){
        $result['text'] = "Company with this name already exist";
        echo json_encode($result);
    } else {
        $uploaddir = '../logo/' . $_POST['cname'] . '/';
        $uploaddir = str_replace(' ', '_',$uploaddir);
        $newClient = $db->prepare("insert into company (cname, address_1, address_2, postcode, city, country_id, phone, website, logo_path, max_user, reminder_days, full_links,
        subdomain, color1, color2, color3, reminder_template, reminder_logo, newsletter_template, newsletter_logo, shortcut) values (:cname, :address_1, :address_2, :postcode, :city, :country_id, :phone, :website, :logo_path, :max_user, :reminder_days, :full_links,
        :subdomain, :color1, :color2, :color3, :reminder_template, :reminder_logo, :newsletter_template, :newsletter_logo, :shortcut)");

        $newClient->bindParam('cname', $_POST['cname']);
        if ($_POST['address_1']) {
            $address_1 = $_POST['address_1'];
        } else {
            $address_1 = null;
        }
        if ($_POST['address_2']) {
            $address_2 = $_POST['address_2'];
        } else {
            $address_2 = null;
        }
        if ($_POST['postcode']) {
            $postcode = $_POST['postcode'];
        } else {
            $postcode = null;
        }
        if ($_POST['city']) {
            $city = $_POST['city'];
        } else {
            $city = null;
        }
        if ($_POST['country'] > 0) {
            $country = $_POST['country'];
        } else {
            $country = null;
        }
        if ($_POST['phone']) {
            $phone = $_POST['phone'];
        } else {
            $phone = null;
        }
        $newClient->bindParam('address_1', $address_1);
        $newClient->bindParam('address_2', $address_2);
        $newClient->bindParam('postcode', $postcode);
        $newClient->bindParam('city', $city);
        $newClient->bindParam('country_id', $country);
        $newClient->bindParam('phone', $phone);
        $newClient->bindParam('website', $_POST['website']);
        $newClient->bindParam('max_user', $_POST['countUsers']);
        $newClient->bindParam('reminder_days', $_POST['reminder_days']);
        if ($_POST['full_links']) {
            $full_links = 1;
        } else {
            $full_links = 0;
        }
        $newClient->bindParam('full_links', $full_links);
        $newClient->bindParam('subdomain', $_POST['subdomain']);
        $newClient->bindParam('reminder_template', $_POST['reminder_template']);
        $newClient->bindParam('newsletter_template', $_POST['newsletter_template']);
        if ($_FILES['logo']) {
            mkdir($uploaddir, 0777);
            $file = $_FILES['logo']['tmp_name'];
            $logo_path = $uploaddir.$_FILES['logo']['name'];
            $logo_path_save = str_replace('..', '',$logo_path);
            if(move_uploaded_file($file, $logo_path)) $newClient->bindParam('logo_path', $logo_path_save);
        }
        if ($_FILES['logoReminder']) {
            $file = $_FILES['logoReminder']['tmp_name'];
            $reminder_path = $uploaddir . $_FILES['logoReminder']['name'];
            $reminder_path_save = str_replace('..', '',$reminder_path);
            if(move_uploaded_file($file, $reminder_path)) $newClient->bindParam('reminder_logo', $reminder_path_save);
        } else {
            $newClient->bindParam('reminder_logo', $logo_path_save);
        }
        if ($_FILES['logoNewsletter']) {
            $file = $_FILES['logoNewsletter']['tmp_name'];
            $newsletter_path = $uploaddir . $_FILES['logoNewsletter']['name'];
            $newsletter_path_save = str_replace('..', '',$newsletter_path);
            if(move_uploaded_file($file, $newsletter_path)) $newClient->bindParam('newsletter_logo', $newsletter_path_save);
        } else {
            $newClient->bindParam('newsletter_logo', $logo_path_save);
        }
        $newClient->bindParam('shortcut', mb_strtoupper($_POST['shotcut']));
        $color1 = str_replace('#', '', $_POST['color1']);
        $color2 = str_replace('#', '', $_POST['color2']);
        $color3 = str_replace('#', '', $_POST['color3']);
        $newClient->bindParam('color1', $color1);
        $newClient->bindParam('color2', $color2);
        $newClient->bindParam('color3', $color3);

        if($newClient->execute()){
            $result['text'] = 'Company '.$_POST['cname'].' successfully added';
            echo json_encode($result);
        }
    }
} else if(isset($_POST['newModule'])){
    $db=getDB();
    $checkModule = $db->prepare("select name from module where name=:name");
    $checkModule->bindParam('name',$_POST['name']);
    $checkModule->execute();
    if(count($checkModule->fetchAll(PDO::FETCH_ASSOC))>0){
        $result['text'] = 'Module with name '.$_POST['name'].' already exist';
        echo json_encode($result);
    } else if(!preg_match("#^[0-9]+$#",$_POST['default_price'])){
            $result['text'] = 'The price should consist only of digits';
            echo json_encode($result);
    } else {
        $newModule = $db->prepare("insert into module (name, description, default_price) values (:name, :description, :default_price)");
        $newModule->bindParam('name', $_POST['name']);
        $newModule->bindParam('description', $_POST['description']);
        if (preg_match("#^[0-9]+$#",$_POST['default_price']) && $_POST['default_price']>0) {
            $default_price=$_POST['default_price'];
        } else {
            $default_price=0;
        }
        $newModule->bindParam('default_price', $default_price);
        if($newModule->execute()){
            $result['text'] = 'Module '.$_POST['name'].' successfully added';
            echo json_encode($result);
        }
    }
} else if(isset($_POST['newUser'])){
$db=getDB();
$checkUser = $db->prepare("select email from \"user\" where email=:email");
$checkUser->bindParam('email',$_POST['email']);
$checkUser->execute();
if(count($checkUser->fetchAll(PDO::FETCH_ASSOC))>0){
    $result['error'] = "User with this email already exist";
    echo json_encode($result);
} else if(!isset($_POST['company_id']) || $_POST['company_id']<1 || !isset($_POST['user_status_id']) || $_POST['user_status_id']<1 || !isset($_POST['security_level_id']) || !isset($_POST['title']) || $_POST['security_level_id']<1){
    $result['error'] = "All fields with the star must be fill in";
    echo json_encode($result);
} else {
    $newUser = $db->prepare("insert into \"user\" (company_id, user_status_id, surname, lastname, phone, mobile, email, password,  created_at, security_level_id, department, title, initials) values
    (:company_id, :user_status_id, :surname, :lastname, :phone, :mobile, :email, 'e10adc3949ba59abbe56e057f20f883e', :created_at, :security_level_id, :department, :title, :initials)");
        $newUser->bindParam('company_id', $_POST['company_id']);
        $newUser->bindParam('user_status_id', $_POST['user_status_id']);
        $newUser->bindParam('surname', trim($_POST['surname']));
        $newUser->bindParam('lastname', trim($_POST['lastname']));
        $newUser->bindParam('email', trim($_POST['email']));
        $newUser->bindParam('security_level_id', $_POST['security_level_id']);
        $newUser->bindParam('department', trim($_POST['department']));
        $newUser->bindParam('title', $_POST['title']);
        $newUser->bindParam('created_at', date('Y-m-d H:m:s'));
        $initials = strtoupper(trim($_POST['surname'][0]) . trim($_POST['lastname'][0]));
        $newUser->bindParam('initials', $initials);
        if ($_POST['phone']) {
            $phone = $_POST['phone'];
        } else {
            $phone = null;
        }
        if ($_POST['mobile']) {
            $mobile = $_POST['mobile'];
        } else {
            $mobile = null;
        }
        $newUser->bindParam('phone', $phone);
        $newUser->bindParam('mobile', $mobile);

        if ($newUser->execute()) {
            $lastUser = $db->prepare("select user_id from \"user\" ORDER by user_id DESC limit 1");
            $lastUser->execute();
            $lastUserRes = $lastUser->fetch(PDO::FETCH_ASSOC);
            $userStat = $db->prepare("insert into user_stat (user_id, seconds_spent_live, logon_count) values (:user_id, 0, 0)");
            $userStat->bindParam('user_id', $lastUserRes['user_id']);
            $userStat->execute();
            $result['text'] = 'User ' . $_POST['surname'] . ' ' . $_POST['lastname'] . ' successfully added';
            echo json_encode($result);
        }
    }
};
?>