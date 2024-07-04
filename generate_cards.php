<?php
require('RouterosAPI.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $number_of_cards = intval($_POST['number_of_cards']);

    $API = new RouterosAPI();

    if ($API->connect('192.168.88.1', 'admin', 'abdullah771735468w')) {
        for ($i = 0; $i < $number_of_cards; $i++) {
            $username = 'user' . uniqid();
            $password = substr(md5(uniqid()), 0, 8);

            $API->comm("/ip/hotspot/user/add", array(
                "name"     => $username,
                "password" => $password,
                "profile"  => "default"
            ));

            echo "الكرت $i: اسم المستخدم: $username، كلمة المرور: $password<br>";
        }
        $API->disconnect();
    } else {
        echo "فشل الاتصال بـ MikroTik API.";
    }
}
?>