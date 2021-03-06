<?php
require_once dirname ( __FILE__ ) . "/BaseDeDatos.php";

class AsociadoDB extends BaseDeDatos {
	
 	const BUSCAR_EMAIL = "SELECT * FROM Asociado WHERE email = '%s'";
 	const BUSCAR_ASOCIADO = "SELECT * FROM Asociado WHERE id = %s";
 	const BUSCAR_ORDEN_LIBRE = "SELECT * FROM Orden WHERE idTransaccion = '%s' AND usadoParaRegistro = 0 AND pagada = 1";
 	const AGREGAR_ASOCIADO = "INSERT INTO Asociado (nombre, email, contrasenia )
 			VALUES ('%s', '%s', SHA2(MD5(('%s')),512))";
 	const CHECAR_CLAVES= "SELECT * FROM Asociado WHERE email = '%s' AND contrasenia = SHA2(MD5(('%s')),512)";
 	const CREAR_SESION= "INSERT INTO Sesion_Asociado (email, token ,fecha)
 			VALUES ('%s', '%s','%s')";
 	const USAR_ORDEN_REGISTRO = "UPDATE Orden SET usadoParaRegistro = 1 WHERE idTransaccion = '%s'";
 	const BUSCAR_ID_ASOCIADO_EN_ORDEN = "SELECT idAsociado FROM Orden WHERE idTransaccion = '%s'";
 	const AGREGAR_REFERENCIADO = "INSERT INTO Referenciado (idPrimario, idSecundario )
 			VALUES ('%s', '%s')";
 	const LEER_CUENTA = "SELECT Asociado.nombre AS nombre, Asociado.email AS email, Asociado.id AS id FROM Sesion_Asociado 
							LEFT JOIN Asociado ON Sesion_Asociado.email = Asociado.email WHERE token = '%s'";
 	const VENTAS_TOTALES_LVL0 = "SELECT BeneficiarioMedica365.nombre AS nombreBeneficiario, Producto.costo AS costo, Producto.nombre AS nombreProducto, Orden.fecha AS fecha 
			FROM Orden 
			LEFT JOIN Producto
			ON Producto.id = Orden.idProducto
			LEFT JOIN BeneficiarioMedica365
			ON BeneficiarioMedica365.certificado = Orden.certificado
			WHERE Orden.idAsociado = '%s' 
			AND Orden.certificado IS NOT NULL 
			AND Orden.fecha >= '%s' AND Orden.fecha <= '%s'";
 	
 	const PAGO_DE_COMISIONES_TOTALES = "SELECT SUM(cantidad) AS cantidad FROM Asociado 
			LEFT JOIN PagoDeComision 
			ON PagoDeComision.idAsociado = Asociado.id 
			WHERE Asociado.id = '%s'";
 	
 	const VENTAS_TOTALES_NIVELES = "SELECT COUNT(Orden.id) AS numeroDeVentas FROM Asociado
		 	LEFT JOIN Referenciado 
			ON Asociado.id = Referenciado.idPrimario
		 	LEFT JOIN Asociado AS lvl1
		 	ON Referenciado.idSecundario = lvl1.id
		 	
		 	LEFT JOIN Referenciado AS rlvl1 
			ON lvl1.id = rlvl1.idPrimario
		 	LEFT JOIN Asociado AS lvl2
		 	ON rlvl1.idSecundario = lvl2.id
		 	
		 	LEFT JOIN Referenciado AS rlvl2 
			ON lvl2.id = rlvl2.idPrimario
		 	LEFT JOIN Asociado AS lvl3
		 	ON rlvl2.idSecundario = lvl3.id
		 	
		 	LEFT JOIN Referenciado AS rlvl3 
			ON lvl3.id = rlvl3.idPrimario
		 	LEFT JOIN Asociado AS lvl4
		 	ON rlvl3.idSecundario = lvl4.id
		 	
		 	LEFT JOIN Orden
		 	ON lvl1.id = Orden.idAsociado
		 	OR lvl2.id = Orden.idAsociado
		 	OR lvl3.id = Orden.idAsociado
		 	OR lvl4.id = Orden.idAsociado
		 	
		 	WHERE Asociado.id = '%s' AND Orden.certificado IS NOT NULL ";
 	
 	const CAMBIAR_DATOS_CUENTA = "UPDATE Asociado SET nombre = '%s', email = '%s' WHERE id = '%s' ";
 	const CAMBIAR_DATOS_CONTRASENA = "UPDATE Asociado SET contrasenia = SHA2(MD5(('%s')),512) WHERE contrasenia = SHA2(MD5(('%s')),512) AND id = '%s' ";
 	
 	function existeAsociado($asociado){
 		$query = sprintf(self::BUSCAR_ASOCIADO, $asociado);
 		$resultado = $this->ejecutarQuery($query);
 		return $this->resultadoTieneValores($resultado);
 	}
 	
 	function existeEmail($email){
 		$query = sprintf(self::BUSCAR_EMAIL, $email);
 		$resultado = $this->ejecutarQuery($query);
 		return $this->resultadoTieneValores($resultado);
 	}
 	
 	function ordenDeCompraLibre($orden){
 		$query = sprintf(self::BUSCAR_ORDEN_LIBRE, $orden);
 		$resultado = $this->ejecutarQuery($query);
 		return $this->resultadoTieneValores($resultado);
 	}
 	
 	function nuevoAsociado($nombre, $email, $contraseña) {
 		$query = sprintf(self::AGREGAR_ASOCIADO, $nombre, $email, $contraseña);
 		$this->ejecutarQuery($query);
 	}
 	
 	function usarOrdenEnRegistro($orden){
 		$query = sprintf(self::USAR_ORDEN_REGISTRO, $orden);
 		$this->ejecutarQuery($query);
 	}
 	
 	function buscarIdAsociado($orden)
 	{
 		$query = sprintf(self::BUSCAR_ID_ASOCIADO_EN_ORDEN, $orden);
 		$resultado = $this->ejecutarQuery($query);
 		return $resultado->fetch_assoc();
 	}
 	
 	function referenciaAsociado($idAsociado, $idNuevoAsociado)
 	{
 		$query = sprintf(self::AGREGAR_REFERENCIADO, $idAsociado, $idNuevoAsociado);
 		$this->ejecutarQuery($query);
 	}
 	
 	function clavesCoinciden($email, $contraseña){
 		$query = sprintf(self::CHECAR_CLAVES, $email, $contraseña);
 		$resultado = $this->ejecutarQuery($query);
 		return $this->resultadoTieneValores($resultado);
 	}
 	
 	function crearSesion($email, $fecha) {
 		$token = md5 (uniqid(mt_rand(), true));
 		$query = sprintf(self::CREAR_SESION, $email, $token,$fecha);
 		$resultado = $this->ejecutarQuery($query);
 		return $token;
 	}
 	
 	function leerCuenta($token) {
 		$query = sprintf(self::LEER_CUENTA, $token);
 		$resultado = $this->ejecutarQuery($query);
 		return $resultado->fetch_assoc();
 	}
 	
 	function ventasTotaleslvl0($idAsociado, $fechaInicio, $fechaFin)
 	{
 		$query = sprintf(self::VENTAS_TOTALES_LVL0, $idAsociado, $fechaInicio, $fechaFin);
 		$resultado = $this->ejecutarQuery($query);
 		return $resultado;
 	}
 	
 	function numeroVentasTotalesNiveles($idAsociado, $fechaInicio, $fechaFin)
 	{
 		$query = sprintf(self::VENTAS_TOTALES_NIVELES, $idAsociado, $fechaInicio, $fechaFin);
 		$resultado = $this->ejecutarQuery($query);
 		return $resultado->fetch_assoc();
 	}
 	
 	
 	function pagoDeComisionesTotales($id)
 	{
 		$query = sprintf(self::PAGO_DE_COMISIONES_TOTALES, $id);
 		$resultado = $this->ejecutarQuery($query);
 		return $resultado->fetch_assoc();
 	}
 	
 	function cambiarDatosCuenta($id, $email, $nombre)
 	{
 		$query = sprintf(self::CAMBIAR_DATOS_CUENTA, $nombre, $email, $id);
 		$this->ejecutarQuery($query);
 	}
 	
 	function contrasenaCoincide($idAsociado, $contraseña)
 	{
 		$query = sprintf(self::CAMBIAR_DATOS_CUENTA, $nombre, $email, $id);
 		$resultado = $this->ejecutarQuery($query);
 	}
 	
 	function cambiarDatosContrasena($id, $contraseña, $contraseñaNueva)
 	{
 		$query = sprintf(self::CAMBIAR_DATOS_CONTRASENA, $contraseñaNueva, $contraseña, $id);
 		$this->ejecutarQuery($query);
 	}
}
?>