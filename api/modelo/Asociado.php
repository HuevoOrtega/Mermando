<?php
require_once dirname ( __FILE__ ) . "/../base-de-datos/AsociadoDB.php";
require_once dirname ( __FILE__ ) . "/../../recursos/PHPMailer/PHPMailerAutoload.php";

class Asociado {
	
	private $dataBase;
	
	public function __construct() {
		$this->dataBase = new AsociadoDB();
	}
	
	public function nuevoAsociado($nombre, $email, $contraseña, $orden)
	{
		if ($this->dataBase->existeEmail($email)) {
			throw new errorEmailUsado();
		}
		if (!$this->dataBase->ordenDeCompraLibre($orden)) {
			throw new errorOrdenUsada();		
		}
		
		$this->dataBase->nuevoAsociado($nombre, $email, $contraseña);
		$idNuevoAsociado = $this->dataBase->mysqli->insert_id;
		
		$this->dataBase->usarOrdenEnRegistro($orden);
		
		$idAsociado = $this->dataBase->buscarIdAsociado($orden)['idAsociado'];
		$this->dataBase->referenciaAsociado($idAsociado, $idNuevoAsociado);
		$asunto = "¡Bienvenido a Vag!";
		$mensaje = "Bienvenido tu numero de usuario es: ".$idNuevoAsociado;
		$this->enviarEmail($asunto, $mensaje, $email);
	}
	
	function enviarEmail($asunto,$mensaje,$destino){
		
		//SMTP needs accurate times, and the PHP time zone MUST be set
		//This should be done in your php.ini, but this is how to do it if you don't have access to that
		date_default_timezone_set('Etc/UTC');
		
		//Create a new PHPMailer instance
		$mail = new PHPMailer;
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 0;
		//Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host = "as1r2064.servwingu.mx";
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = 26;
		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;
		//Username to use for SMTP authentication
		$mail->Username = "vagmx";
		//Password to use for SMTP authentication
		$mail->Password = 'NnTt2$mhH*d';
		
		$mail->Subject = $asunto;
		$mail->Body    = $mensaje;
		
		$mail->setFrom('ventas@vag.mx', 'Vag');
		$mail->addAddress($destino);						// Name is optional
		
		$mail->isHTML(true);                                // Set email format to HTML
		
		
		
		if(!$mail->send()) {
			echo 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
			//echo "enviado";
		}
	}
	
	
	function iniciarSesion($email, $contraseña) {
		
		if (!$this->dataBase->existeEmail($email)) {
			throw new errorEmailNoExiste();
		}
		if (!$this->dataBase->clavesCoinciden($email, $contraseña)){
			throw new errorClavesNoCoinciden();
		}
		date_default_timezone_set ( 'America/Mexico_City' );
		$fecha = date ( "Y-m-d H:i:s");
		return $this->dataBase->crearSesion($email, $fecha);
	}
	
	function leerCuenta($token) {
		return $this->dataBase->leerCuenta($token);
	}
	
	function leerComisionActual($id) {
		date_default_timezone_set ( 'America/Mexico_City' );
		$fecha = date ( "Y-m-d");
		$ventaslvl0Lista = $this->ventaslvl0($id,'2017-01-01',$fecha);		
		$comisionTotallvl0 = count($ventaslvl0Lista) * 50;
		
		
		$numeroDeVentasNiveles = $this->ventasNiveles($id, '2017-01-01', $fecha);
		$comisionTotalNiveles = $numeroDeVentasNiveles * 10;
		
		
		$pagoDeComisionesTotales = $this->dataBase->pagoDeComisionesTotales($token)['cantidad'];
		
		//echo $comisionTotallvl0.'---'.$comisionTotalNiveles.'---'.$pagoDeComisionesTotales;
		
		return $comisionTotallvl0 + $comisionTotalNiveles - $pagoDeComisionesTotales;
	}
	
	function ventaslvl0($id, $fechaInicio, $fechaFin)
	{
		$ventaslvl0 = $this->dataBase->ventasTotaleslvl0($id, $fechaInicio, $fechaFin);
		for ($ventaslvl0Lista = array(); $fila = $ventaslvl0->fetch_assoc(); $ventaslvl0Lista[] = $fila);
		return $ventaslvl0Lista;
	}
	
	function ventasNiveles($id, $fechaInicio, $fechaFin)
	{
		$ventasNiveles = $this->dataBase->numeroVentasTotalesNiveles($id, $fechaInicio, $fechaFin);
		return $ventasNiveles['numeroDeVentas'];
	}
	
	function cambiarDatosCuenta($token, $email, $nombre)
	{
		$idAsociado = $this->leerCuenta($token)['id'];
		$this->dataBase->cambiarDatosCuenta($idAsociado, $email, $nombre);
	}
	
	function cambiarDatosContrasena($token, $contraseña, $contraseñaNueva)
	{
		$asociado = $this->leerCuenta($token);
		if (!$this->dataBase->clavesCoinciden($asociado['email'], $contraseña)){
			throw new errorClavesNoCoinciden();
		}
		$this->dataBase->cambiarDatosContrasena($asociado['id'], $contraseña, $contraseñaNueva);
	}
}

class errorEmailUsado extends Exception{
}
class errorOrdenUsada extends Exception{
}
class errorEmailNoExiste extends Exception{
}
class errorClavesNoCoinciden extends Exception{
}
?>