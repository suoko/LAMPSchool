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

require_once '../php-ini'.$_SESSION['suffisso'].'.php';
require_once '../lib/funzioni.php';
//require_once '../lib/ db / query.php';

//$lQuery = LQuery::getIstanza();

// istruzioni per tornare alla pagina di login se non c'è una sessione valida
////session_start();

$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
$iddocente = $_SESSION["idutente"];

if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=".$_SESSION['suffisso']);
    die;
}

$titolo = "Iscrizione docente a corso Moodle";
$script = "";
stampa_head($titolo, "", $script,"SMP");
stampa_testata("<a href='../login/ele_ges.php'>PAGINA PRINCIPALE</a> - $titolo", "", "$nome_scuola", "$comune_scuola");

$con=mysqli_connect($db_server,$db_user,$db_password,$db_nome) or die ("Errore durante la connessione: ".mysqli_error($con));

$idcorso=stringa_html("corso");

$query="select iddocente,cognome, nome
        from tbl_docenti";
        
$ris=mysqli_query($con,inspref($query));

while ($rec=mysqli_fetch_array($ris))
{
	$stralu="sms".$rec['iddocente'];
	$aludainv=stringa_html($stralu);
	if ($aludainv=="on")
	{
             $usernamedocente=costruisciUsernameMoodle($rec['iddocente']);
             print "<br>Docente: ".$rec['cognome']." ".$rec['nome'];
             $identdocente=getIdMoodle($tokenservizimoodle,$urlmoodle,$usernamedocente);
             iscriviUtenteMoodle($tokenservizimoodle,$urlmoodle,$idcorso,$identdocente,3);
	}       
}      

print "	<center>  <form method='post' id='formlez' action='seleiscrizionecorsidoc.php'>
              <input type='submit' value='Indietro'>
			  </form></center>
			  ";


mysqli_close($con);
stampa_piede("");