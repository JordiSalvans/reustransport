<?php

	require_once "bbdd.php";
	require_once "simple_html_dom.php";
	header('Content-Type: text/html; charset=utf-8');

	ini_set('max_execution_time', 300);
	libxml_use_internal_errors(true);
	
	function recuperarLinies($dbh) {
		$dbh->prepare("DELETE FROM linies")->execute();

		$html = file_get_html("http://www.reustransport.cat/online/lineas.php?web=web");

	   	$liniesUl = $html->find("ul")[1];
	   	$links = $liniesUl->find('a');
	   	for ($i = 1; $i < count($links); $i++) {
	   		$idLinia = explode("&recorrido=",explode("esquema.php?id_linia=", $links[$i]->attr['href'])[1])[0];
	        $nomLinia = $links[$i]->find('p')[0]->plaintext;
	        $image = "http://www.reustransport.cat/online/" . $links[$i]->find('img')[0]->attr['src'];
	        copy($image, '../img/linies/'.$idLinia.'.png');
	        $stmt = $dbh->prepare("INSERT INTO linies (id, nom) VALUES (:id, :nom)");
			$stmt->bindParam(':id', $idLinia);
			$stmt->bindParam(':nom', $nomLinia);
			$stmt->execute();
	    }
	}

	function recuperarParades($dbh) {
		$dbh->prepare("DELETE FROM parades")->execute();

		$html = file_get_html("http://www.reustransport.cat/online/paradas.php");

	   	$paradaBuscadaList = $html->find('div[id=paradaBuscadaList]')[0];
	   	$links = $paradaBuscadaList->find('a');
	   	for ($i = 0; $i < count($links); $i++) {
	   		$partLink = explode("llegadas.php?id_parada=", $links[$i]->attr['href'])[1];
	   		$idParada = explode("&amp;parada=", $partLink)[0];
	        $nomParada = $links[$i]->plaintext;
	        if($nomParada != '') {
		        $stmt = $dbh->prepare("INSERT INTO parades (id, nom) VALUES (:id, :nom)");
				$stmt->bindParam(':id', $idParada);
				$stmt->bindParam(':nom', $nomParada);
				$stmt->execute();
			}
	    }
	}

	function recuperarLiniaParada($dbh) {
		$dbh->prepare("DELETE FROM linia_parada")->execute();
		$linies = array();
		foreach($dbh->query('SELECT id from linies') as $row) {
	        array_push ($linies , $row['id']);
	    }

	   	foreach ($linies as $linia) {
			$parades = array();
			foreach($dbh->query('SELECT * from linia_parada WHERE linia = ' . $linia) as $row) {
		        array_push ($parades , $row['parada']);
		    }
			$html = file_get_html("http://www.reustransport.cat/online/esquema.php?id_linia=" . $linia);

	   		$liniesUl = $html->find("ul")[0];
		   	$links = $liniesUl->find('a');
		   	$ordre = 1;
		   	for ($i = 0; $i < count($links); $i++) {
		   		$idParada = explode("&parada=",explode("llegadas.php?id_parada=", $links[$i]->attr['href'])[1])[0];

		        if(!in_array($idParada, $parades)) {
		        	$stmt = $dbh->prepare("INSERT INTO linia_parada (id, linia, parada, ordre) VALUES (:id, :linia, :parada, :ordre)");
		        	$liniaParada = $linia . "_" . $idParada;
					$stmt->bindParam(':id', $liniaParada);
					$stmt->bindParam(':linia', $linia);
					$stmt->bindParam(':parada', $idParada);
					$stmt->bindParam(':ordre', $ordre);
					$stmt->execute();

					$ordre++;
		        }
		    }
	   	}
	}

	function recuperarHorari($dbh) {
		$dbh->prepare("DELETE FROM horari")->execute();
		$dbh->prepare("ALTER TABLE horari AUTO_INCREMENT = 1")->execute();
		foreach($dbh->query('SELECT * from linia_parada') as $row) {
			$html = file_get_html("http://www.reustransport.cat/online/horarios.php?id_parada=".$row['parada']."&id_linia=L".$row['linia']);

		   	$tipusDies = $html->find("div[class=titolHorari]");
		   	$horariDia = $html->find("table[class=principal]");

		   	for ($d = 0; $d < count($tipusDies); $d++) {
		   		$tipusDia = strtolower($tipusDies[$d]->plaintext);
		   		$segundas = $horariDia[$d]->find("table[class=segunda]");
		   		for ($s = 1; $s < count($segundas); $s++) {
		   			$hores = $segundas[$s]->find('td');
		   			for ($h = 0; $h < count($hores); $h++) {
		   				$hora = $hores[$h]->plaintext;
		   				$stmt = $dbh->prepare("INSERT INTO horari (linia, parada, tipus_dia, hora) VALUES (:linia, :parada, :tipusDia, :hora)");
						$stmt->bindParam(':linia', $row['linia']);
						$stmt->bindParam(':parada', $row['parada']);
						$stmt->bindParam(':tipusDia', $tipusDia);
						$stmt->bindParam(':hora', $hora);
						$stmt->execute();
		   			}
		   		}
		   	}
	    }
	}

	$time_start = microtime(true); 
	recuperarLinies($dbh);
	recuperarParades($dbh);
	recuperarLiniaParada($dbh);
	recuperarHorari($dbh);
	$time_end = microtime(true);

	$execution_time = ($time_end - $time_start);

	echo '<b>Temps total d\'execusió de l\'script:</b> '.$execution_time.' segons';

?>