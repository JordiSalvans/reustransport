<?php

	require_once "bbdd.php";

 	$post = json_decode(file_get_contents("php://input"));

	$funcio = property_exists($post, "function") ? $post->function : "";

	if($funcio == "carregarParades") {
		$parades = array();
		foreach ($dbh->query('SELECT * from parades ORDER BY nom ASC') as $row) {
			array_push($parades, $row);
		}
		echo json_encode($parades);
	} else if($funcio == "carregarParadesByOrigen") {
		$origen = property_exists($post, "origen") ? $post->origen : 0;
		if($origen > 0) {
			$parades = array();
			foreach ($dbh->query('SELECT DISTINCT p.* FROM linia_parada lp1, linia_parada lp2, parades p WHERE lp1.parada = '
					.$origen.' AND lp1.linia = lp2.linia AND lp1.parada != lp2.parada AND lp2.parada = p.id ORDER BY p.nom ASC') as $row) {
				array_push($parades, $row);
			}
			echo json_encode($parades);
		} else {
			http_response_code(400);
		}
	} else if($funcio == "carregarHorari") {
		$origen = property_exists($post, "origen") ? $post->origen : 0;
		$desti = property_exists($post, "desti") ? $post->desti : 0;
		if($origen > 0 && $desti > 0) {
			$sqlString = "SELECT h.linia 'linia', h.tipus_dia 'dia', p.id 'parada', p.nom 'nom_parada', h.hora 'hora'
							FROM horari h, parades p WHERE h.parada = p.id AND (p.id = :origen OR p.id = :desti)
							AND h.linia IN (SELECT DISTINCT lp1.linia FROM linia_parada lp1, linia_parada lp2 WHERE lp1.parada = :origen AND lp2.parada = :desti AND lp1.linia = lp2.linia)
							ORDER BY h.linia, h.tipus_dia, h.hora ASC";
			$stmt = $dbh->prepare($sqlString);
			$stmt->bindParam(':origen', $origen);
			$stmt->bindParam(':desti', $desti);
			$stmt->execute();
			$data = $stmt->fetchAll();

			echo json_encode($data);
		} else {
			http_response_code(400);
		}
	} else {
		http_response_code(400);
	}

?>