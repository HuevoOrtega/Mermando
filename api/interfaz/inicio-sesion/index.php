<?php
require_once dirname(__FILE__)."/../../modelo/SafeString.php";
require_once dirname(__FILE__)."/../../modelo/Asociado.php";

header('Content-Type: text/html; charset=utf8');

if (!isset($_POST['email']) || !isset($_POST['contrasenia'])) {
			die(json_encode(array("Status"=>"ERROR missing values")));
		}

	try{
		$email = SafeString::safe($_POST['email']);
		$contraseņa = SafeString::safe($_POST['contrasenia']);
		
		$asociado = new Asociado();
		
		$token = $asociado->iniciarSesion($email, $contraseņa);
		
		echo json_encode(array("status"=>"ok","token"=>$token));
		
	} catch(errorConBaseDeDatos$e) {
		echo json_encode(array("status"=>"error","clave"=>"db","explicacion"=>$e->getMessage()));
	} catch(errorEmailNoExiste $e) {
		echo json_encode(array("status"=>"error","clave"=>"email"));
	} catch(errorClavesNoCoinciden $e) {
		echo json_encode(array("status"=>"error","clave"=>"claves"));
	} catch (Exception $e) {
		echo json_encode(array("status"=>"error","clave"=>"desconocido","explicacion"=>$e->getMessage()));
 	}
?>