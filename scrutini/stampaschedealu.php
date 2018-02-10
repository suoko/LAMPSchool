<?php

session_start();

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

$con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

// istruzionii per tornare alla pagina di login se non c'� una sessione valida
////session_start();


$tipoutente = $_SESSION["tipoutente"]; //prende la variabile presente nella sessione
if ($tipoutente == "")
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

$classe = stringa_html('classe');
$idalunno = stringa_html('idalunno');
$periodo = stringa_html('periodo');
$datastampa = stringa_html('data');
$firmadirigente = stringa_html('firma');
$gioass = stringa_html('gioass');


if ($idalunno != $_SESSION['idutente'] && $tipoutente == 'T')
{
    header("location: ../login/login.php?suffisso=" . $_SESSION['suffisso']);
    die;
}

if ($classe != "")
    $elencoalunni = estrai_alunni_classe_data($classe, $fineprimo, $con);

$alunni = array();
if ($classe != "")
{

    $query = "select idalunno from tbl_alunni where idalunno in ($elencoalunni) order by cognome,nome";
    $ris = mysqli_query($con, inspref($query));
    while ($val = mysqli_fetch_array($ris))
    {
        $alunni[] = $val['idalunno'];
    }
}
else
{

    $alunni[] = $idalunno;
    $query = "select idclasse from tbl_alunni where idalunno=$idalunno";
    $ris = mysqli_query($con, inspref($query));
    if ($val = mysqli_fetch_array($ris))
    {
        $classe = $val['idclasse'];
    }
}

/*
 * Salvo o leggo i dati di modifica delle stampe
 */

if ($datastampa != "" & $firmadirigente != "" & $gioass != "")  // VUOL DIRE CHE VENGO DALLA STAMPA IN SEDE DI SCRUTINI
// ALTRIMENTI DEVO LEGGERE I DATI SALVATI
{
    $query = "select sostituzioni from tbl_scrutini where idclasse=$classe and periodo='$periodo'";
    $ris = mysqli_query($con, inspref($query));
    $rec = mysqli_fetch_array($ris);

    $posmodi = strpos($rec['sostituzioni'], "{");

    if ($posmodi === false)
        $posmodi = -1;

    if ($posmodi >= 0)
        $strini = substr($rec['sostituzioni'], 0, $posmodi);
    else
        $strini = $rec['sostituzioni'];


    $modistampa = "{" . $datastampa . substr($gioass, 0, 2) . $firmadirigente;


    $querymod = "update tbl_scrutini set sostituzioni='" . $strini . $modistampa . "' where idclasse=$classe and periodo='$periodo'";

    mysqli_query($con, inspref($querymod)) or die("Errore $querymod");
}
else
{
    $query = "select sostituzioni from tbl_scrutini where idclasse=$classe and periodo='$periodo'";
    $ris = mysqli_query($con, inspref($query));
    $rec = mysqli_fetch_array($ris);

    $posmodi = strpos($rec['sostituzioni'], "{");
    if ($posmodi === false)
        $posmodi = -1;
    if ($posmodi >= 0)
    {
        $modistampa = substr($rec['sostituzioni'], $posmodi);

        $datastampa = substr($modistampa, 1, 10);

        $gioass = substr($modistampa, 11, 2);

        if ($gioass == 'ye')
            $gioass = 'yes';
        $firmadirigente = substr($modistampa, 13);
    }
    else
    {
        $datastampa = data_italiana(estrai_datascrutinio($classe, $periodo, $con));

        $gioass = 'yes';
        $firmadirigente = estrai_dirigente($con);
    }
}




stampa_schede($alunni, $periodo, $classe, $datastampa, $firmadirigente, $gioass);

mysqli_close($con);

function stampa_schede($alunni, $periodo, $classe, $datastampa, $firmadirigente, $gioass)
{

    @require("../php-ini" . $_SESSION['suffisso'] . ".php");

    require_once("../lib/fpdf/fpdf.php");

    $schede = new FPDF();

    $con = mysqli_connect($db_server, $db_user, $db_password, $db_nome) or die("Errore durante la connessione: " . mysqli_error($con));

    $datascrutinio = data_italiana(estrai_datascrutinio($classe, $periodo, $con));

    $larghezzanote = 100;

    foreach ($alunni as $alu)
    {
        $schede->AddPage();

        $schede->Image('../immagini/repubblica.png', 95, NULL, 13, 15);

        //$schede->Image('../immagini/miur.png',35,NULL,120,10);
        $schede->SetFont('Times', 'B', 10);
        $schede->Cell(190, 8, converti_utf8("Ministero dell'Istruzione, dell'Università e della Ricerca"), NULL, 1, "C");
        $schede->SetFont('Arial', 'B', 10);
        $schede->Cell(190, 6, converti_utf8("$nome_scuola"), NULL, 1, "C");
        $schede->SetFont('Arial', 'BI', 9);
        $schede->Cell(190, 6, converti_utf8("$comune_scuola"), NULL, 1, "C");
        $schede->SetFont('Arial', 'BI', 9);
        $specplesso = converti_utf8($plesso_specializzazione . ": " . decodifica_classe_spec($classe, $con));
        $schede->Cell(190, 6, $specplesso, NULL, 1, "C");
        $schede->SetFont('Arial', 'B', 10);

        $per = $periodo . "° ";
        if ($numeroperiodi == 3)
            $per .= "trimestre";
        else
            $per .= "quadrimestre";
        $per = converti_utf8($per);
        $annoscolastico = $annoscol . "/" . ($annoscol + 1);
        $schede->Cell(190, 6, "SCHEDA DI VALUTAZIONE PERIODICA: $per - A.S. $annoscolastico", NULL, 1, "C");
        $schede->Cell(190, 4, "", NULL, 1, "C");

        // Prelievo dei dati degli alunni

        $datanascita = "";
        $codfiscale = "";
        $denominazione = "";
        $query = "SELECT datanascita, codfiscale, denominazione FROM tbl_alunni,tbl_comuni
              WHERE tbl_alunni.idcomnasc=tbl_comuni.idcomune 
              AND idalunno=$alu";
        $ris = mysqli_query($con, inspref($query));
        if ($val = mysqli_fetch_array($ris))
        {
            $datanascita = data_italiana($val['datanascita']);
            $codfiscale = $val['codfiscale'];
            $denominazione = converti_utf8($val['denominazione']);
        }

        // CLASSE
        $schede->SetFont('Arial', '', 8);
        $schede->Cell(25, 6, "Classe: ", 0);
        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(30, 6, decodifica_classe_no_spec($classe, $con, 1), 1);
        // COGNOME NOME ALUNNO
        $schede->SetFont('Arial', '', 8);
        $schede->Cell(25, 6, "Alunno: ", 0);
        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(110, 6, converti_utf8(decodifica_alunno($alu, $con)), 1, 1);

        // DATA NASCITA
        $schede->SetFont('Arial', '', 8);
        $schede->Cell(25, 6, "Data nascita: ", 0);
        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(30, 6, $datanascita, 1);
        // COMUNE DI NASCITA
        $schede->SetFont('Arial', '', 8);
        $schede->Cell(25, 6, "Com. nasc.: ", 0);
        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(50, 6, $denominazione, 1);
        // CODICE FISCALE
        $schede->SetFont('Arial', '', 8);
        $schede->Cell(10, 6, "C.F. ", 0);
        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(50, 6, $codfiscale, 1, 1);


        // ESTRAGGO LE VALUTAZIONI PERIODICHE

        $schede->SetFont('Arial', 'BI', 8);
        $schede->Cell(190, 8, "VALUTAZIONI PERIODICHE", NULL, 1, "C");


        $query = "SELECT distinct tbl_materie.idmateria,sigla,denominazione,tipovalutazione
		        FROM tbl_cattnosupp,tbl_materie 
                WHERE tbl_cattnosupp.idmateria=tbl_materie.idmateria
                and tbl_cattnosupp.idclasse=$classe
                and tbl_materie.progrpag<99
                and tbl_cattnosupp.iddocente <> 1000000000
                order by tbl_materie.progrpag,tbl_materie.sigla";
        $rismat = mysqli_query($con, inspref($query)) or die("Errore: " . inspref($query));
        while ($valmat = mysqli_fetch_array($rismat))
        {

            $denom = converti_utf8($valmat['denominazione']);
            $larghezzanote = 100;
            $stampa = true;
            // VERIFICO SE SI TRATTA DI UNA MATERIA DI CATTEDRA SPECIALE
            // E NEL CASO VERIFICO SE L'ALUNNO SEGUE LA MATERIA

            $query = "select distinct tbl_gruppi.idgruppo from tbl_gruppialunni,tbl_alunni,tbl_gruppi
                where tbl_gruppi.idgruppo=tbl_gruppialunni.idgruppo
                and tbl_gruppialunni.idalunno=tbl_alunni.idalunno
                and tbl_alunni.idclasse=$classe
                and tbl_gruppi.idmateria=" . $valmat['idmateria'] . "";
            $risgru = mysqli_query($con, inspref($query)) or die("Errore: " . inspref($query));
            if ($recgru = mysqli_fetch_array($risgru))
            {
                $idgruppo = $recgru['idgruppo'];
                $query = "select * from tbl_gruppialunni where
		            idalunno=$alu and idgruppo IN (
		            select distinct tbl_gruppi.idgruppo from tbl_gruppialunni,tbl_alunni,tbl_gruppi 
                where tbl_gruppi.idgruppo=tbl_gruppialunni.idgruppo
                and tbl_gruppialunni.idalunno=tbl_alunni.idalunno
                and tbl_alunni.idclasse=$classe
                and tbl_gruppi.idmateria=" . $valmat['idmateria'] . ")";

                $risgrualu = mysqli_query($con, inspref($query)) or die("Errore: " . inspref($query));
                if (mysqli_num_rows($risgrualu) == 0)
                    $stampa = false;
            }

            // FINE CONTROLLO
            if ($stampa)
            {
                $idmateria = $valmat['idmateria'];
                $scritto = "";
                $orale = "";
                $pratico = "";
                $unico = "";
                $assenze = "";
                $annotazioni = "";
                $query = "SELECT votoscritto,votoorale,votopratico,votounico,assenze,note FROM tbl_valutazionifinali
				  WHERE idalunno=$alu
				  AND periodo='$periodo'
				  AND idmateria=$idmateria";

                $risvoti = mysqli_query($con, inspref($query));

                if ($recvoti = mysqli_fetch_array($risvoti))
                {


                    $scritto = dec_to_pag($recvoti['votoscritto']);
                    $orale = dec_to_pag($recvoti['votoorale']);
                    $pratico = dec_to_pag($recvoti['votopratico']);
                    $unico = dec_to_pag($recvoti['votounico']);
                    $assenze = $recvoti['assenze'];
                    $annotazioni = converti_utf8($recvoti['note']);
                }
                $schede->SetFont('Arial', 'B', 7);
                $schede->Cell(55, 6, "$denom", 0);  // TTTT
                $valutazione = "";
                if ($scritto != "")
                {
                    $valutazione = "S: " . $scritto . "  ";
                }
                if ($orale != "")
                {
                    $valutazione = $valutazione . "O: " . $orale . "  ";
                }
                if ($pratico != "")
                {
                    $valutazione = $valutazione . "P: " . $pratico . "  ";
                }
                if ($unico != "")
                {
                    $valutazione = $unico;
                }
                $schede->SetFont('Arial', 'BI', 7);
                $schede->Cell(35, 6, $valutazione, 1, 0, 'C');


                // SE SI TRATTA DI SCUOLA SUPERIORE STAMPO LE ORE DI ASSENZA
                if ($livello_scuola == 4)
                {
                    $schede->SetFont('Arial', '', 7);
                    $y = $schede->GetY();
                    $schede->Cell(20, 6, "Ass. $assenze", 1, 0, 'C');
                    $larghezzanote -= 20;
                }
                // SE PREVISTA LA STAMPA DEI GIUDIZI STAMPO LE ANNOTAZIONI ALTRIMENTI STAMPO UNA CELLA VUOTA

                if ($giudizisuscheda == 'yes')
                {
                    $schede->SetFont('Arial', '', 7);
                    $y = $schede->GetY();
                    $schede->Multicell($larghezzanote, 3, $annotazioni, 0, 1);
                    if ($schede->GetY() < ($y + 6))
                    {
                        $schede->SetY($y + 6);
                    }
                }
                else
                {
                    $schede->Multicell($larghezzanote, 6, "", 0, 1);
                }
            }
        }
        // AGGIUNGO IL VOTO DI COMPORTAMENTO
        $query = "SELECT denominazione,votounico,note FROM tbl_valutazionifinali,tbl_materie
              WHERE tbl_valutazionifinali.idmateria=tbl_materie.idmateria 
              AND idalunno=$alu
              AND periodo='$periodo'
              AND tbl_valutazionifinali.idmateria=-1
              ORDER BY denominazione";
        $risvoti = mysqli_query($con, inspref($query));

        if ($recvoti = mysqli_fetch_array($risvoti))
        {
            $denom = $recvoti['denominazione'];
            $unico = dec_to_pag($recvoti['votounico']);
            $annotazioni = converti_utf8($recvoti['note']);

            $schede->SetFont('Arial', 'B', 7);
            $schede->Cell(55, 6, "$denom", 0);
            $valutazione = "";

            if ($unico != "")
            {
                $valutazione = $unico;
            }
            $schede->SetFont('Arial', 'BI', 7);
            $schede->Cell(35, 6, $valutazione, 1, 0, 'C');
            // SE SI TRATTA DI SCUOLA SUPERIORE STAMPO UNA CELLA VUOTA PER LE ORE DI ASSENZA
            if ($livello_scuola == 4)
            {
                $schede->SetFont('Arial', '', 7);
                $y = $schede->GetY();
                $schede->Cell(20, 6, "", 1, 0, 'C');
            }

            // SE PREVISTA LA STAMPA DEI GIUDIZI STAMPO LE ANNOTAZIONI ALTRIMENTI STAMPO UNA CELLA VUOTA
            if ($giudizisuscheda == 'yes')
            {
                $schede->SetFont('Arial', '', 7);
                $y = $schede->GetY();
                $schede->Multicell($larghezzanote, 3, $annotazioni, 0, 1);
                if ($schede->GetY() < ($y + 6))
                {
                    $schede->SetY($y + 6);
                }
            }
            else
            {
                $schede->Multicell($larghezzanote, 6, "", 0, 1);
            }
        }

        // ESTRAGGO IL NUMERO DI ASSENZE


        if ($gioass == 'yes')
        {
            $perioquery = "and true";
            if ($periodo == "1")
            {
                $perioquery = " and data <= '" . $fineprimo . "'";
            }
            if ($periodo == "2" & $numeroperiodi == 2)
            {
                $perioquery = " and data > '" . $fineprimo . "'";
            }
            if ($periodo == "2" & $numeroperiodi == 3)
            {
                $perioquery = " and data > '" . $fineprimo . "' and data <=  '" . $finesecondo . "'";
            }
            if ($periodo == "3")
            {
                $perioquery = " and data > '" . $finesecondo . "'";
            }

            $query = "select count(*) as numassenze from tbl_assenze where idalunno=$alu $perioquery";
            $risasse = mysqli_query($con, inspref($query));

            if ($recasse = mysqli_fetch_array($risasse))
            {
                if ($livello_scuola != 4)
                {
                    //$numasse = $recasse['numassenze'];
                    //$schede->SetFont('Arial', 'B', 7);
                    //$schede->Cell(55, 6, "Numero assenze", 0);
                    //$schede->SetFont('Arial', 'BI', 8);
                    //$schede->Cell(35, 6, $numasse, 1, 1, 'C');
                }
            }
        }
        // ESTRAGGO IL GIUDIZIO DISCORSIVO SE PREVISTO
        if ($giudizisuscheda == "yes")
        {
            $query = "SELECT giudizio from tbl_giudizi
						WHERE idalunno=$alu
						AND periodo='$periodo'";
            $risgiud = mysqli_query($con, inspref($query));
            if ($recgiud = mysqli_fetch_array($risgiud))
            {

                $giudizio = $recgiud['giudizio'];
                $giudizio = converti_utf8($giudizio);
                $giudizio = trim($giudizio);
                if (strlen(trim($giudizio)) != 0)
                {
                    $schede->SetFont('Arial', 'BI', 8);
                    $schede->Cell(190, 8, "GIUDIZIO", NULL, 1, "C");
                    $schede->SetFont('Arial', '', 7);
                    $schede->Multicell(190, 4, $giudizio, 1, 1);
                }
            }
        }
        $schede->SetFont('Arial', '', 7);
        // LUOGO E DATA SCRUTINIO

        $luogodata = "$comune_scuola, $datastampa";
        $schede->SetY($schede->GetY() + 8);
        $schede->Cell(95, 8, $luogodata, 0, 1, 'L');

        // FIRMA DEL DIRIGENTE
        $schede->Cell(95, 8, "", 0, 0, 'C');
        $schede->Multicell(95, 6, converti_utf8("\nIl dirigente scolastico\n" . $firmadirigente . "\n"), "", "C");

        //  $schede->Cell(95,8,"Il Dirigente Scolastico",0,1,'C');

        $suff = "";
        if ($_SESSION['suffisso'] != "")
        {
            $suff = $_SESSION['suffisso'] . "/";
        }
        else
        {
            $suff = "";
        }
        if (estrai_dirigente($con) == $firmadirigente)
        {
            $schede->Image('../abc/' . $suff . 'firmadirigente.png', 120, NULL);
        }
        $schede->SetY($schede->GetY() - 40);

        $schede->Image('../abc/' . $suff . 'timbro.png', 85, NULL);
        //$schede->Cell(95,8,"",0,0,'C');
        //$schede->Cell(95,8,"_______________________",0,1,'C');
        // FIRMA DEL TUTORE LEGALE
        //   $schede->Cell(95,8,"Firma del tutore legale",0,1,'C');
        //   $schede->Cell(95,8,"_______________________",0,1,'C');
    }


    $nomefile = "pagelle_" . decodifica_classe($classe, $con) . "_" . $periodo . ".pdf";
    $nomefile = str_replace(" ", "_", $nomefile);

    $schede->Output($nomefile, "I");


    mysqli_close($con);
}
