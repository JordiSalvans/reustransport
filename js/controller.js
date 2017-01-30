var scope;

horarisReusApp.controller('horarisReusController', function ($scope, $http, $location, $timeout) {
	scope = $scope;

	$scope.paradaOrigen = '';
	$scope.paradaDesti = '';

	$scope.textSearch = '';
	$scope.tipusSearch = 'origen';

	$scope.carregarParadesOrigen = function() {
		$('.loader').show();
		$http.post('php/ajax.php', {'function': 'carregarParades'})
			.success(function(respuesta, status, headers, config) {
				$scope.paradesOrigen = respuesta;
				$('.loader').hide();
			}).error(function(respuesta, status, headers, config) {
				$('.loader').hide();
  				$('#modalError').openModal();
			});
	};

	$scope.carregarParadesDesti = function() {
		console.log("Parada origen: " + $scope.paradaOrigen);
		$('.loader').show();
		$scope.paradaDesti = '';
		$scope.horari = [];
		$http.post('php/ajax.php', {'function': 'carregarParadesByOrigen', 'origen': $scope.paradaOrigen})
			.success(function(respuesta, status, headers, config) {
				$scope.paradesDesti = respuesta;
				$('.loader').hide();
			}).error(function(respuesta, status, headers, config) {
				$('.loader').hide();
  				$('#modalError').openModal();
			});
	};

	$scope.carregarHorari = function() {
		console.log("Parada destí: " + $scope.paradaDesti);
		$('.loader').show();
		$http.post('php/ajax.php', {'function': 'carregarHorari', 'origen': $scope.paradaOrigen, 'desti': $scope.paradaDesti})
			.success(function(respuesta, status, headers, config) {
				$scope.horari = [];
				var nomOrigen = "";
				var nomDesti = "";
				for(var i = 0; i < respuesta.length; i++) {
					if(respuesta[i].parada == $scope.paradaOrigen)
						nomOrigen = respuesta[i].nom_parada;
					else if(respuesta[i].parada == $scope.paradaDesti)
						nomDesti = respuesta[i].nom_parada;
					
				}

				var linia = {'num': respuesta[0].linia, 'dies': []};
				var dia = {'nom': respuesta[0].dia, 'origen': {'nom': nomOrigen, 'hores': []}, 'desti': {'nom': nomDesti, 'hores': []}};
				for(var i = 0; i < respuesta.length; i++) {
					var hora = respuesta[i];
					if(dia.nom != hora.dia) {
						linia.dies.push(dia);
						dia = {'nom': hora.dia, 'origen': {'nom': nomOrigen, 'hores': []}, 'desti': {'nom': nomDesti, 'hores': []}};
					}
					if(linia.num != hora.linia) {
						$scope.horari.push(linia);
						linia = {'num': hora.linia, 'dies': []};
					}

					if(hora.parada == $scope.paradaOrigen)
						dia.origen.hores.push(hora.hora);
					else if(hora.parada == $scope.paradaDesti)
						dia.desti.hores.push(hora.hora);
				}
				linia.dies.push(dia);
				$scope.horari.push(linia);
				$('.loader').hide();
			}).error(function(respuesta, status, headers, config) {
				$('.loader').hide();
  				$('#modalError').openModal();
			});
	};

	$scope.getParadesBySearch = function(text) {
		var array = [];
		var tempParades = $scope.tipusSearch == 'origen' ? $scope.paradesOrigen : $scope.paradesDesti;
		if(tempParades != undefined) {
			for (var i = 0; i < tempParades.length; i++) {
				if(tempParades[i].nom.toLowerCase().indexOf(text.toLowerCase()) > -1) {
					array.push(tempParades[i]);
				}
			}
		}
		return array;
	};

	$scope.selectSearch = function(id) {
		if($scope.tipusSearch == 'origen') {
			$scope.paradaOrigen = id;
			$scope.carregarParadesDesti();
		} else {
			$scope.paradaDesti = id;
			$scope.carregarHorari();
		}
		$('#modalSearch').closeModal();
	};

	$('.modal-trigger').leanModal();

	$scope.carregarParadesOrigen();

});