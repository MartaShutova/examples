<?php include('../inc/header-login.php'); ?>
<main class="container">
    <div class="row">
        <div class="col-xs-12 col-md-3"></div>
        <div class="col-xs-12 col-md-6">
            <div id="response">
                <pre>Bitte melden Sie sich an.</pre>
            </div>
            <h1>Login</h1>
            <div class="form-group">
                <form id="login">
                    <br>
                    <label>E-Mail</label>
                    <input type="text" class="form-control" name="email" placeholder="E-Mail"  id="email">
                    <br>
                    <label>Passwort</label>
                    <input type="password" class="form-control"  name="pwd" placeholder="Passwort" id="pwd">
                    <a href="passwort-vergessen.php">Passwort vergessen?</a>
                    <br><br>
                    <a id="anmelden" class="btn btn-primary">Anmelden</a><br>
                </form>
                <br>
            </div>
            <div class="col-xs-12 col-md-3"></div>
        </div>
    </div><!-- row -->
</main><!-- /.container -->

<?php include('../inc/footer.php'); ?>
<script>
    $(document).ready(function() {
        <!--Teilformulare per Javascript übermitteln-->
        $("#articleform").submit(function(event) {
            event.preventDefault();
        });
    });
    $(document).on('click', '#anmelden', function () {
        var form=$('#login');
        $.ajax({
            url : "../ajax/login.php",
            type: 'POST',
            data : form.serialize(),
        }).success(function(data) {
            var result = jQuery.parseJSON(data);
            var response = result['text'];
            var ref = result['ref'];
            $('#response pre').html( response );
            if(response === "Sie werden in Kürze weitergeleitet.") location.href = ref;
        });
    });
</script>