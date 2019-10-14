<?php
include('../inc/header-admin-login.php');
$title = 'Admin';

require_once('../ajax/system/db.php');
require_once('../ajax/system/user.php');
$db=getDB();
//Startschleife für alle Reminder
$qry = "select * from company order by cname ASC";
$smtx = $db->prepare($qry);
$smtx -> execute();

$cat = "select * from user_status";
$stat = $db->prepare($cat);
$stat -> execute();
$arrStatus=[];
while(($status = $stat->fetch(PDO::FETCH_OBJ))) {
    array_push($arrStatus, $status);
}
//echo '<pre/>';
//print_r($arrStatus);
$users = "select * from \"user\" ORDER by surname asc";
$allusers = $db->prepare($users);
$allusers -> execute();
$arrUsers=[];
while(($users = $allusers->fetch(PDO::FETCH_OBJ))) {
    array_push($arrUsers, $users);
}
$authors = "select * from \"user\" WHERE security_level_id >=7 ORDER by surname asc";
$allAuthors = $db->prepare($authors);
$allAuthors -> execute();
$arrAuthors=[];
while($author = $allAuthors->fetch(PDO::FETCH_OBJ)) {
    array_push($arrAuthors, $author);
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<main class="container">
    <div class="adminListAll" id="adminList">
        <ul>
            <li id="list1" style="background-color: #dbf9ca" class="adminList">Benutzerstatus wechseln abhängend von Firma</li>
            <li id="list2" class="adminList">Status eines Benutzers ändern</li>
            <li id="list3" class="adminList">Benutzerstatistik</li>
            <li id="list4" class="adminList">API ausprobieren</li>
            <li id="list5" class="adminList">Löschen von Artikeln und Kategorien</li>
            <li id="list6" class="adminList">Gewählte Artikel löschen</li>
            <li id="list7" class="adminList">Artikeltext ändern</li>
            <li id="list8" class="adminList">Neue Firma/User/Modul</li>
        </ul>
    </div>
    <div class="partsAll">
        <div id="part1" style="display: block;" class="adminStyle">
            <h2>Benutzerstatus wechseln abhängend von Firma</h2>
            <form id="block" class="form-group">
                <input type="hidden" name="changeStatusByCompany">
                <label>Firma wählen</label>
                <select id="country" name="country" class="form-control" required>
                    <option>Kein</option>
                    <?php while(($xrow = $smtx->fetch(PDO::FETCH_OBJ))) {?>
                        <option value="<?php echo $xrow->company_id?>"><?php echo $xrow->cname?></option>
                    <?php }?>
                </select><br>
                <label>Status wählen</label>
                <select id="status" name="status" class="form-control" required>
                    <option>Kein</option>
                    <?php foreach ($arrStatus as $usersStatus) {?>
                        <option value="<?php echo $usersStatus->user_status_id; ?>"><?php echo $usersStatus->status_name; ?></option>
                    <?php }?>
                </select><br>
                <a id="blockCompany" class="btn btn-primary">Anwenden</a><br>
            </form>
        </div><!-- row -->
        <div id="part2" class="adminStyle">
            <h2>Status eines Benutzers ändern</h2>
            <form id="inactive" class="form-group">
                <input type="hidden" name="changeUserStatus">
                <label>Benutzer wählen</label>
                <select id="user" name="user" class="form-control" required>
                    <option>Kein</option>
                    <?php foreach ($arrUsers as $user) { ?>
                        <option value="<?php echo $user->user_id; ?>"><?php echo $user->surname.' '.$user->lastname; ?></option>
                    <?php } ?>
                </select><br>
                <label>Status wählen</label>
                <select id="status2" name="status2" class="form-control" required>
                    <option>Kein</option>
                    <?php foreach ($arrStatus as $usersStatus) {?>
                        <option value="<?php echo $usersStatus->user_status_id; ?>"><?php echo $usersStatus->status_name; ?></option>
                    <?php }?>
                </select><br>
                <a id="blockUser" class="btn btn-primary">Beützerstatus ändern</a><br>
            </form>
        </div><!-- row -->
        <div id="part3" class="adminStyle">
            <h2>Statistik</h2>
            <button id="userStat" class="btn btn-primary">Benutzerstatistiken</button>
            <button id="articleStat" class="btn btn-primary">Statistik Artikel</button>
            <button id="statisticByUser" class="btn btn-primary">Statistiken nach Benutzer</button>
            <ul class="usersList" id="articleStatistick" style="display: none;">
                <li id="maxReadind" class="btn btn-default" onclick="getStatistics(2,'Die am meisten gelesenen Artikel','Anzahl der Ansichten')">Die am meisten gelesenen Artikel</li>
                <li id="maxExport" class="btn btn-default" onclick="getStatistics(3,'Am häufigsten exportierte Artikel','Wie oft wurde der Artikel exportiert')">Am häufigsten exportierte Artikel</li>
                <li id="maxEmail" class="btn btn-default" onclick="getStatistics(7,'Die am häufigsten gesendeten Artikel','Wie oft wurde der Artikel weitergeleitet')">Die am häufigsten gesendeten Artikel</li>
                <li id="maxComment" class="btn btn-default" onclick="getStatistics(4,'Am häufigsten kommentierte Artikel','Wie oft wurde der Artikel kommentiert')">Am häufigsten kommentierte Artikel</li>
                <li id="maxMerkliste" class="btn btn-default" onclick="getStatistics(5,'Artikel Hinzugefügt Merkliste','Wie oft wurde der Artikel Hinzugefügt Merkliste')">Artikel Hinzugefügt Merkliste</li>
            </ul>
            <div id="statisticByAllUsers" style="width: 100%; display: block;"></div>
            <div id="statisticByAllArticles" style="width: 100%; display: block;"></div>
            <div id="statisticByUsers" style="width: 100%; display: block;"></div>
            <div id="statisticByUsersResult" style="width: 100%; display: block;"></div>
        </div><!-- row -->
        <div id="part4" class="adminStyle">
            <h2>API ausprobieren</h2>
            <button id="api" class="btn btn-primary">API - letzte 10 Artikel</button>
            <ul class="api list-group news-overview news-overview-small"></ul>
        </div><!-- row -->
        <div id="part5" class="adminStyle">
            <h2>Artikel eines bestimmten Benutzers löschen</h2>
            <form id="toDeleteArticles" class="form-group">
                <input type="hidden" name="deleteArticles">
                <label>Benutzer wählen</label>
                <select id="editor" name="editor" class="form-control" required>
                    <option>Kein</option>
                    <?php foreach ($arrAuthors as $author) {?>
                        <option value="<?php echo $author->user_id; ?>"><?php echo $author->surname.' '.$author->lastname; ?></option>
                    <?php }?>
                </select><br>
                <a id="delArti" class="btn btn-primary">Artikel löschen</a><br>
            </form>
            <h2>Löschen von Kategorien mit EWZ</h2>
            <a id="delCatEwz" class="btn btn-primary">Löschen von Kategorien</a><br>
            <div id="delCatEwzRez"></div>
        </div><!-- row -->
        <div id="part6" class="adminStyle">
            <h2>Gewählte Artikel löschen</h2>
            <a href="../admin/articles.php" class="btn btn-primary">Zum Artikel löschen</a><br>
        </div><!-- row -->
        <div id="part7" class="adminStyle">
            <h2>Artikeltext ändern</h2>
            <form action="findArticles.php" method="post" id="change" class="form-group">
                <input type="hidden" name="changeText">
                <input type="text" class="form-control" name="findText" placeholder="Text finden">
                <button id="replaceText" class="btn btn-primary" style="margin-top: 15px;">Text in Artikel finden</button><br>
            </form>

        </div><!-- row -->
        <div id="part8" class="adminStyle">
            <h2>Neue Einträge</h2>
            <a id="addNewCompany" class="btn btn-primary new" style="margin-top: 15px;" >Neue Firma</a>
            <a id="addNewModule" class="btn btn-primary new" style="margin-top: 15px;" >Neues Modul</a>
            <a id="addNewUser" class="btn btn-primary new" style="margin-top: 15px;" >Neuer User</a>
            <div id="addResult"></div>
            <div id="partCompany" class="newPart" style="display: none">
                <h2>Neue Firmenkunden erstellen</h2>
                <form id="addCompany" class="form-group">
                    <input type="hidden" name="newCompany">
                    <label>Kundenname</label>
                    <input type="text" class="form-control" name="cname" placeholder="Kundennamen eingeben" required>
                    <label>Adresse 1</label>
                    <input type="text" class="form-control" name="address_1" placeholder="Adresse 1 eingeben">
                    <label>Adresse 2</label>
                    <input type="text" class="form-control" name="address_2" placeholder="Adresse 2 eingeben">
                    <label>PLZ</label>
                    <input type="text" class="form-control" name="postcode" placeholder="PLZ eingeben">
                    <label>Stadt</label>
                    <input type="text" class="form-control" name="city" placeholder="Stadt eingeben">
                    <label>Land</label>
                    <select name="country" class="form-control">
                        <option value="0">Land wählen</option>
                        <?php
                        $db=getDB();
                        $qry = "select * from country order by country ASC";
                        $smtc = $db->prepare($qry);
                        $smtc -> execute();
                        while($country = $smtc->fetch(PDO::FETCH_OBJ)) {?>
                            <option value="<?php echo $country->country_id; ?>"><?php echo $country->country; ?></option>
                        <?php }
                        ?>
                    </select>
                    <label>Telefonnummer</label>
                    <input type="text" class="form-control" name="phone" placeholder="Telefonnummer eingeben">
                    <label>Webseite</label>
                    <input type="text" class="form-control" name="website" placeholder="Webseite eingeben" required>
                    <label>Vorhandene Anzahl an Benutzer</label>
                    <input type="text" class="form-control" name="countUsers" value="0">
                    <label>Anzahl der Tage bis zum Reminder (Standart: alle 7 Tage)</label>
                    <input type="text" class="form-control" name="reminder_days" value="7">
                    <p>Auswählen, falls benötigt (normalerweise kann man frei lassen):</p>
                    <div>
                        <input type="checkbox" name="full_links" value="1">
                        <label>full_links = 1</label>
                    </div>
                    <label>Subdomain</label>
                    <input type="text" class="form-control" name="subdomain" placeholder="Subdomain eingeben" required>
                    <label>Reminder Vorlage</label>
                    <input type="text" class="form-control" name="reminder_template" value="reminder.html">
                    <label>Newsletter Vorlage</label>
                    <input type="text" class="form-control" name="newsletter_template" value="newsletter.html">
                    <label>Logo</label>
                    <input type="file" accept="image/x-png,image/jpeg" class="form-control" name="logo" placeholder="Upload logo" onchange="checkImage(this)" required>
                    <a id="openAnotherLogos">Hier klicken um mehr Bilder für Reminder und Newsletter hochzuladen.</a>
                    <div id="anotherLogos">
                    </div>
                    <label>Shotcut</label>
                    <input type="text" class="form-control" name="shotcut" placeholder="Shotcut" required>
                    <p>Farbe für Firmenkunden</p>
                    <div style="display: flex;">
                        <div>
                            <input type="text" id="color1" name="color1" value="#1F5C1F" placeholder="Color_1">
                            <div id="colorpicker1"></div>
                        </div>
                        <div>
                            <input type="text" id="color2" name="color2" value="#c9982b" placeholder="Color_2">
                            <div id="colorpicker2"></div>
                        </div>
                        <div>
                            <input type="text" id="color3" name="color3" value="#b7b7b5" placeholder="Color_3">
                            <div id="colorpicker3"></div>
                        </div>
                    </div>
                    <input type="submit" id="newCompany" class="btn btn-primary" style="margin-top: 15px;" value="Neue Firma speichern"><br>
                </form>
            </div>
            <div id="partModule" class="newPart" style="display: none">
                <h2>Neues Modul erstellen</h2>
                <form id="addModule" class="form-group">
                    <input type="hidden" name="newModule">
                    <label>Modulname</label>
                    <input type="text" class="form-control" name="name" minlength="3" placeholder="Modulname eingeben" required>
                    <label>Bezeichnung</label>
                    <input type="text" class="form-control" name="description" minlength="5"  placeholder="Bezeichnung eingeben" required>
                    <label>Preis</label>
                    <input type="text" class="form-control" name="default_price" placeholder="Preis eingeben">
                    <a id="newModule" class="btn btn-primary" style="margin-top: 15px;">Neues Modul abspeichern</a><br>
                </form>
            </div>
            <div id="partUser" class="newPart" style="display: none">
                <h2>Neuen Benutzer erstellen</h2>
                <form id="addUser" class="form-group">
                    <input type="hidden" name="newUser">
                    <label>Vorname</label>
                    <input type="text" class="form-control" name="surname" placeholder="Vornamen eingeben *" required>
                    <label>Nachname</label>
                    <input type="text" class="form-control" name="lastname" placeholder="Nachnamen eingeben *" required>
                    <label>Firmenwahl</label>
                    <select name="company_id" class="form-control">
                        <option selected disabled="disabled">Firma wählen *</option>
                        <?php
                        $db=getDB();
                        $companies = $db->prepare("select * from company order by cname ASC");
                        $companies -> execute();
                        while($company = $companies->fetch(PDO::FETCH_OBJ)) {?>
                            <option value="<?php echo $company->company_id; ?>"><?php echo $company->cname; ?></option>
                        <?php }
                        ?>
                    </select>
                    <label>Kundenstatus</label>
                    <select name="user_status_id" class="form-control">
                        <option selected disabled="disabled">Kundenstatus wählen *</option>
                        <?php
                        $db=getDB();
                        $statuses = $db->prepare("select * from user_status order by status_name ASC");
                        $statuses -> execute();
                        while($status = $statuses->fetch(PDO::FETCH_OBJ)) {?>
                            <option value="<?php echo $status->user_status_id; ?>"><?php echo $status->status_name; ?></option>
                        <?php }
                        ?>
                    </select>
                    <label>Telefonnummer</label>
                    <input type="text" class="form-control" name="phone" placeholder="Telefonnummer eingeben">
                    <label>Mobilfunknummer</label>
                    <input type="text" class="form-control" name="mobile" placeholder="Mobilfunknummer eingeben">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Email eingeben *">
                    <label>Zugangsstufe</label>
                    <select name="security_level_id" class="form-control">
                        <option selected disabled="disabled">Zugangsstufe wählen *</option>
                        <?php
                        $db=getDB();
                        $sec_levels = $db->prepare("select * from security_level order by name ASC");
                        $sec_levels -> execute();
                        while($sec_level = $sec_levels->fetch(PDO::FETCH_OBJ)) {?>
                            <option value="<?php echo $sec_level->security_level_id; ?>"><?php echo $sec_level->name; ?></option>
                        <?php }
                        ?>
                    </select>
                    <label>Abteilung</label>
                    <input type="text" class="form-control" name="department" placeholder="Abteilung wählen">
                    <label>Geschlecht *</label>
                    <div>
                        <input type="radio" class="" name="title" value="Herr"><label>Herr</label>
                        <input type="radio" class="" name="title" value="Frau"><label>Frau</label>
                    </div>
                    <a id="newUser" class="btn btn-primary" style="margin-top: 15px;">Neuen Benützer speichern</a><br>
                </form>
            </div>
        </div>
    </div>
</main><!-- /.container -->
<div id="response">   </div>
<br/>
<?php include('../inc/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js"></script>
<script>
    $(document).on('click', '#blockCompany', function () {
        var form=$('#block');
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : form.serialize(),
        }).success(function(data) {
            var result = JSON.parse(data);
            var response = result['text'];
            var ref = result['ref'];
            $('#response').html("<p>Benützerstatus erfolgreich geändert!</p>");
        });
    });

    $(document).on('click', '#blockUser', function () {
        var form=$('#inactive');
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : form.serialize(),
        }).success(function(data) {
            var result = JSON.parse(data);
            var response = result['text'];
            var ref = result['ref'];
            $('#response').html("<p>Benützerstatus erfolgreich geändert!</p>");
        });
    });

    $(document).on('click', '#delArti', function () {
        var form=$('#toDeleteArticles');
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : form.serialize(),
        }).success(function(data) {
            var result = JSON.parse(data);
            var response = result['text'];
            var ref = result['ref'];
            $('#response').html(response);
        });
    });

    $(document).on('click', '#api', function(){
        $.ajax({
            url : "../api/",
            type: 'POST',
            data : 'limit=10',
        }).success(function(data) {
            $('ul.api').html(JSON.stringify(data));
        });
    });
    $(document).ready(function() {
        $('#colorpicker1').farbtastic('#color1');
        $('#colorpicker2').farbtastic('#color2');
        $('#colorpicker3').farbtastic('#color3');
        $(".adminList").click(function() {
            var click_id = $(this).attr("id");
            $('.adminList').css('background-color', '#ffffff');
            $('#'+click_id).css('background-color', '#dbf9ca');
            click_id = click_id.replace('list', 'part');
            $('.adminStyle').css('display', 'none');
            $('#'+click_id).css('display', 'block');
        });
        $("#openAnotherLogos").click(function() {
            var text = '<label>Reminder Logo</label><input type="file" accept="image/x-png,image/jpeg" class="form-control" name="logoReminder" placeholder="Reminder Logo" onchange="checkImage(this)"><label>Newsletter Logo</label><input type="file" class="form-control" name="logoNewsletter" placeholder="Newsletter Logo" onchange="checkImage(this)">';
            $('#anotherLogos').append(text);
            $('#openAnotherLogos').css('display', 'none');
        });
        $(".new").click(function() {
            var click_id = $(this).attr("id");
            $(".newPart").css('display', 'none');
            click_id = click_id.replace('addNew', 'part');
            $('#'+click_id).css('display', 'block');
        });
    });

    $(document).on('click', '#statisticByUser', function () {
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : {'statisticByUser': "statisticByUser"},
        }).success(function(data) {
            var result = JSON.parse(data);
            if (result.length > 0) {
                var text = '<ul class="usersList">';
                for (var i=0; i<result.length; i++){
                    text+='<li id=user_'+result[i].user_id+' onclick="personalStatistic('+result[i].user_id+')">'+result[i].user_name+'</li>';
                }
                text += '</ul>';
                $('#statisticByUsers').append(text);
            }
        });
    });

    $(document).on('click', '#articleStat', function(){
        if($('#articleStatistick').css('display') == 'none'){
            $('#articleStatistick').css('display','grid');
        } else {
            $('#articleStatistick').css('display','none');
        }

    });
    $(document).on('click', '#userStat', function(){
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : {'statistics': "user"},
        }).success(function(data) {
            var result = JSON.parse(data);
            var detailnamesusers = [];
            var detailnumsusers = [];
            var detailtimeusers = [];
            if (result) {
                if (result.length > 0) {
                    for (var i = 0; i < result.length; i++) {
                        detailnamesusers.push(result[i].user_name);
                        detailnumsusers.push(result[i].counts);
                        detailtimeusers.push(result[i].time);
                    }
                    var rez ='<canvas id="userStatistics"></canvas>';
                    $('#statisticByAllUsers').prepend(rez);
                    var ctx = document.getElementById('userStatistics').getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: detailnamesusers,
                            datasets: [{
                                backgroundColor: 'rgb(255, 199, 132)',
                                borderColor: 'rgb(135, 99, 132)',
                                data: detailnumsusers
                            }]
                        },
                        options: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'Anzahl der Anmeldungen von Benutzern',
                                fontSize: 20
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        min: 0
                                    },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Anzahl',
                                        fontSize: 16
                                    }
                                }]
                            }
                        }
                    });
                    var rez1 ='<canvas id="userStatistics1"></canvas>';
                    $('#statisticByAllUsers').append(rez1);
                    var ctx = document.getElementById('userStatistics1').getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: detailnamesusers,
                            datasets: [{
                                backgroundColor: 'rgb(255, 199, 132)',
                                borderColor: 'rgb(135, 99, 132)',
                                data: detailtimeusers
                            }]
                        },
                        options: {
                            legend: {display: false},
                            title: {
                                display: true,
                                text: 'Verweildauer von Nutzern auf der Website',
                                fontSize: 20
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        min: 0
                                    },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Zeit, Minuten',
                                        fontSize: 16
                                    }
                                }]
                            }
                        }
                    });
                }
            }
        });
    });
    function getStatistics(type, diagramName, scaleLabel) {
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : {'articleStatistics': type},
        }).success(function(data) {
            var result = JSON.parse(data);
            var detailIdArticles = [];
            var detailCountArticles = [];
            if (result) {
                if (result.length > 0) {
                    for (var i = 0; i < result.length; i++) {
                        detailIdArticles.push(result[i].article_id);
                        detailCountArticles.push(result[i].article_count);
                    }
                    var rez ='<canvas id="userStatistics'+type+'"></canvas>';
                    $('#statisticByAllArticles').prepend(rez);
                    var canvas = document.getElementById('userStatistics' + type);
                    var ctx = canvas.getContext('2d');
                    var chart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: detailIdArticles,
                            datasets: [{
                                backgroundColor: 'rgb(255, 1'+type+'9, 13'+type+')',
                                borderColor: 'rgb(135, 99, 132)',
                                data: detailCountArticles
                            }]
                        },
                        options: {
                            legend: {display: false},
                            title: {
                                display: true,
                                text: diagramName,
                                fontSize: 20
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        min: 0
                                    },
                                    scaleLabel: {
                                        display: true,
                                        labelString: scaleLabel,
                                        fontSize: 16
                                    }
                                }],
                                yAxes: [{
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Artikelnummer',
                                        fontSize: 16
                                    }
                                }]
                            }
                        }
                    });
                }
            }
        });
    }
    function personalStatistic(click_user_id) {
        $('#user_'+click_user_id).css('background-color', '#83d487');
        $.ajax({
            url : "../ajax/admin.php",
            type: 'POST',
            data : {'StatisticForChoosingUser': click_user_id},
        }).success(function(data) {
            var result = JSON.parse(data);
            console.log(result.articlesByUser);
            var userStatisticResult = '';
            if (result.byUser.user_name) {
                userStatisticResult = '<h3>' + result.byUser.user_name + '</h3>';
                userStatisticResult += '<table class="table"><tr><td>Email</td><td>' + result.byUser.email + '</td></tr>';
                userStatisticResult += '<tr><td>Status</td><td>' + result.byUser.user_status + '</td></tr>';
                userStatisticResult += '<tr><td>Sicherheit</td><td>' + result.byUser.security_level + '</td></tr>';
                userStatisticResult += '<tr><td>Letzter Eingang</td><td>' + result.byUser.last_logon + '</td></tr>';
                userStatisticResult += '<tr><td>Anzahl der Anmeldungen</td><td>' + result.byUser.counts + '</td></tr>';
                userStatisticResult += '<tr><td>Zeit auf der Website verbracht</td><td>' + Math.round(result.byUser.time / 60) + ' minuten</td></tr>';
                userStatisticResult += '</table>';
            }
            if (result.articlesByUser[0].length > 0) {
                userStatisticResult += '<table class="table"><tr><th>Artikelnummer</th><th>Anzahl</th><th>Operation</th></tr>';
                for (var i=0; i<result.articlesByUser.length; i++){
                    for (var j=0; j<result.articlesByUser[i].length; j++) {
                        userStatisticResult += '<tr><td><a href="../artikel.php?article='+result.articlesByUser[i][j].article_id+'">' + result.articlesByUser[i][j].article_id + '</a></td>';
                        userStatisticResult += '<td>' + result.articlesByUser[i][j].article_count + '</td>';
                        userStatisticResult += '<td>' + result.articlesByUser[i][j].astname + '</td></tr>';
                    }
                }
                userStatisticResult += '</table>';
            }
            $('#statisticByUsersResult').prepend(userStatisticResult);
        });
    }
    $(document).on('click', '#newCompany', function () {
        var form=$('#addCompany')[0];
        var required = ["cname", "website", "subdomain", "shotcut", "logo", "countUsers", "reminder_days", "reminder_template", "newsletter_template", "color1", "color2", "color3"];
        var required_show = ["Name", "Webseite", "Subdomain", "Shotcut", "Logo", "Benützeranzahl", "Tage bis zum Reminder", "Reminder Vorlage", "Newsletter Vorlage", "Farbe color1", "Farbe color2", "Farbe color3"];
        for(var j=0; j<required.length; j++) {
            for (var i = 0; i < form.length; i++) {
                if (form.elements[i].name == required[j] && form.elements[i].value == "") {
                    alert('Es fehlt: ' + required_show[j]);
                    form.elements[i].focus();
                    return false;
                }
            }
        }
        var data = new FormData(form);
        data.append("CustomField", "Noch extra Data zum Testen");
        $("#newCompany").prop("disabled", true);
        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            url: "../admin/newRecord.php",
            data: data,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 600000,
            success: function (data) {
                var result = JSON.parse(data);
                $(".newPart").css('display', 'none');
                $("#addResult").text(result['text']);
                $("#newCompany").prop("disabled", false);
            },
            error: function (e) {
                $("#addResult").text(e.responseText);
                $("#newCompany").prop("disabled", false);

            }
        });
    });
    $(document).on('click', '#newModule', function () {
        var formStart=$('#addModule');
        var form=$('#addModule')[0];
        var required = ["name", "description", "default_price"];
        var required_show = ["Name", "Beschreibung", "default_price"];
        for(var j=0; j<required.length; j++) {
            for (var i = 0; i < form.length; i++) {
                if (form.elements[i].name == required[j] && form.elements[i].value == "") {
                    alert('Es fehlt: ' + required_show[j]);
                    form.elements[i].focus();
                    return false;
                }
            }
        }
        $.ajax({
            url : "../admin/newRecord.php",
            type: 'POST',
            data : formStart.serialize(),
        }).success(function(data) {
            $(".newPart").css('display', 'none');
            var result = JSON.parse(data);
            $("#addResult").text(result['text']);
        });
    });
    $(document).on('click', '#newUser', function () {
        var formStart=$('#addUser');
        var form=$('#addUser')[0];
        console.log(form);
        var requiredUsers = ["surname", "lastname", "email", "department"];
        for(var j=0; j<requiredUsers.length; j++) {
            for (var i = 0; i < form.length; i++) {
                if (form.elements[i].name == requiredUsers[j] && form.elements[i].value == "") {
                    $("#addResult").text("Alle Felder mit einem Stern müssen ausgefüllt sein!");
                    form.elements[i].focus();
                    return false;
                }
            }
        }
        $.ajax({
            url : "../admin/newRecord.php",
            type: 'POST',
            data : formStart.serialize(),
        }).success(function(data) {
            var result = JSON.parse(data);
            if(result['error']){
                $("#addResult").text(result['error']);
            } else if(result['text']){
                $(".newPart").css('display', 'none');
                $("#addResult").text(result['text']);
            }
        });
    });
    function checkImage(image) {
        $("#newCompany").prop("disabled", false);
        var arr = image['value'].split('.');
        if(arr[arr.length-1] != 'png' && arr[arr.length-1] != 'jpeg' && arr[arr.length-1] != 'jpg'){
            alert('Bilder nur im .png oder .jpeg Format erlaubt!');
            $("#newCompany").prop("disabled", true);
        }
    }
    $(document).on('click', '#delCatEwz', function(){
        if(confirm("Sind Sie sicher?")) {
            $.ajax({
                url: "../ajax/admin.php",
                type: 'POST',
                data: 'delCatEwz',
            }).success(function (data) {
                var result = JSON.parse(data);
                if (result>0){
                    $("#delCatEwzRez").text(result+' Kategorien wurden erfolgreich gelöscht');
                } else {
                    $("#delCatEwzRez").text('Es gibt keine Kategorien zu löschen');
                }
            });
        }
    });
//    $(document).on('click', '#stat', function(){
//        $.ajax({
//            url : "../ajax/admin.php",
//            type: 'POST',
//            data : {'statistics': "user"},
//        }).success(function(data) {
//            // console.log(data);
//            var result = JSON.parse(data);
//            // var response = result['text'];
//            // $('#userStatistics').html(response);
//            var detailnames = [];
//            var detailnums = [];
//            if (result) {
//                if (result.text[0].companies.length > 0) {
//                    for(var i=0;i<result.text[0].companies.length;i++) {
//                        detailnames.push(result.text[0].companies[i].cname);
//                        if (result.text[0].companies[i].cusers) {
//                            detailnums.push(result.text[0].companies[i].cusers);
//                        }
//                        else {
//                            detailnums.push(0);
//                        }
//                    }
//                }
//            }
//            // console.log(detailnames + ' | ' + detailnums + ' | ' + result.text[0].numberUser);
//            var ctx = document.getElementById('userStatistics').getContext('2d');
//            var chart = new Chart(ctx, {
//                // The type of chart we want to create
//                type: 'line',
//
//                // The data for our dataset
//                data: {
//                    labels: ['Amount of users'],
//                    datasets: [{
//                        label: 'Active users',
//                        backgroundColor: 'rgb(255, 99, 132)',
//                        borderColor: 'rgb(255, 99, 132)',
//                        data: [result.text[0].numberUser]
//                    }]
//                },
//
//                // Configuration options go here
//                options: {}
//            });
//
//            var ctx = document.getElementById('userStatistics2').getContext('2d');
//            var chart = new Chart(ctx, {
//                // The type of chart we want to create
//                type: 'bar',
//
//                // The data for our dataset
//                data: {
//                    labels: detailnames,
//                    datasets: [{
//                        label: 'Active company users',
//                        backgroundColor: 'rgb(255, 99, 132)',
//                        borderColor: 'rgb(255, 99, 132)',
//                        data: detailnums
//                    }]
//                },
//
//                // Configuration options go here
//                options: {}
//            });
//
//        });
//    });
</script>