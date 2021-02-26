<?php
if ($_POST['user1']) {
    $user = $_POST['user1'];
    $pass = $_POST['pass1'];
    $header = $_POST['header1'];
    update_option("netgsm_user", $user);
    update_option("netgsm_pass", $pass);
    update_option("netgsm_header", $header);
}
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<div class="container">
    <h2>NETGSM SMS Ayarları</h2>
    <form action="" method="POST">
        <div class="form-group">
            <label for="user">Kullanıcı adı:</label>
            <input type="text" class="form-control" id="user" value="<?php echo get_option("netgsm_user"); ?>" placeholder="NETGSM kullanıcı adı" name="user1">
        </div>
        <div class="form-group">
            <label for="pass">Şifre:</label>
            <input type="text" class="form-control" id="pass" value="<?php echo get_option("netgsm_pass"); ?>" placeholder="NETGSM şifreniz" name="pass1">
        </div>
        <div class="form-group">
            <label for="header">Kullanıcı adı:</label>
            <input type="text" class="form-control" id="header" value="<?php echo get_option("netgsm_header"); ?>" placeholder="NETGSM kullanıcı adı" name="header1">
        </div>
        <button type="submit" class="btn btn-default">KAYDET</button>
    </form>
</div>

</body>
</html>