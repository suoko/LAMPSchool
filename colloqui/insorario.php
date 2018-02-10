<?php session_start();

/*
Copyright (C) 2015 Pietro Tamburrano
Questo programma è un software libero; potete redistribuirlo e/o modificarlo secondo i termini della 
GNU Affero General Public License come pubblicata 
dalla Free Software Foundation; sia la versione 3, 
sia (a vostra scelta) ogni versione successiva.

Questo programma é distribuito nella speranza che sia utile 
ma SENZA ALCUNA GARANZIA; senza anche l'implicita garanzia di 
POTER ESSERE VENDUTO o di IDONEITA' A UN PROPOSITO PARTICOLARE. 
Vedere la GNU Affero General Public License per ulteriori dettagli.

Dovreste aver ricevuto una copia della GNU Affero General Public License
in questo programma; se non l'avete ricevuta, vedete http://www.gnu.org/licenses/
*/

@require_once("../php-ini" . $_SESSION['suffisso'] . ".php");
@require_once("../lib/funzioni.php");

// istruzioni per tornare alla pagina di login se non c'� una sessione valida
////session_start();
$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

$titolo = "Inserimento orario";
$script = "";
stampa_head($titolo, "", $script,"AMSP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - <a href='orario.php'>Orario</a> - $titolo", "", "$nome_scuola", "$comune_scuola");

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die ("Errore durante la connessione: " . mysqli_error($con));

$query = "UPDATE tbl_orario SET valido=0 WHERE 1=1";
$ris = mysqli_query($con, inspref($query)) or die ("Errore nella query: " . mysqli_error($con));

for ($g = 1; $g <= $giornilezsett; $g++)
{
    for ($h = 1; $h <= $numeromassimoore; $h++)
    {

        $ora = "_" . $g . "_" . $h;
        $valini = stringa_html("inizio$ora");
        $valfin = stringa_html("fine$ora");

               
        if (checktime($valini) && checktime($valfin))
        {
            $query = "insert into tbl_orario(giorno,ora,inizio,fine)
			        values ($g,$h,'$valini','$valfin')";
            mysqli_query($con, inspref($query)) or die ("Errore: " . inspref($query));
        }

    }
}

print "<center><br><b>Orario registrato!</b><br></center>";
stampa_piede("");
mysqli_close($con);

function checktime($ora)
{
    $contr = true;
    if (substr($ora, 0, 2) < "00" | substr($ora, 0, 2) > "23")
    {
        return false;
    }
    if (substr($ora, 3, 2) < "00" | substr($ora, 3, 2) > "59")
    {
        return false;
    }
    if (substr($ora, 2, 1) != ":")
    {
        return false;
    }
    return $contr;
}


