<?php
class Menu {
    
    private $scripts;
	private $rutas;
	private $menus;
	private $token;
	
	public function __construct(){
		$this->scripts = "";
		$this->rutas = "";
		$this->token = Utilidades::randomString(7);
	}
	
	public function getMenuLogin(){
		$this->setScripts($this->getDependenciasLogin());
	}
	
	public function getMenu($idUsuario) {
        
        $conexion = new Conexion ();
		$sql = "SELECT  m.id_menu,
						m.titulo AS 'menu',
						om.descripcion AS 'opcion',
						om.url,
						om.vista,
						om.controlador,
						om.controlador_src
				FROM usuario_rol ur 
				INNER JOIN menu_rol mr 
                ON ur.id_rol = mr.id_rol 
                INNER JOIN menu m 
				ON m.id_menu = mr.id_menu 
				INNER JOIN menu_opcion mo
				ON m.id_menu = mo.id_menu
				INNER JOIN opcion_menu om
				ON mo.id_opcion_menu = om.id_opcion_menu 
                WHERE ur.id_usuario = :id_usuario 
				GROUP BY om.descripcion, om.url
				ORDER BY mo.orden";
		
		try {

			$st = $conexion->prepare ( $sql );
			$st->bindValue ( ':id_usuario', $idUsuario, PDO::PARAM_INT );
				
			if ($st->execute ()) {
				$controladores = array ();
				$opciones = $st->fetchAll ( PDO::FETCH_ASSOC );
		
				$b = false;
				$co = "";
				$menu = array ();
				$menuAux = array ();
				$opcionesMenu = null;
				$r = "";
				foreach ( $opciones as $opcion ) {
					$b = false;
					foreach($menu as &$mn){
						if($mn["menu"] == $opcion["menu"]){
							$mn["opciones"][] = array(
									"url" => $opcion ['url'],
									"nombre" => $opcion ['opcion']
							);
							$b = true;
							break;
						}
					}
					
					if($b == false){
						$menuAux = array (
							"menu" => $opcion ['menu'],
							"opciones" => array()
						);
						
						$menuAux["opciones"][] =  array(
									"url" => $opcion ['url'],
									"nombre" => $opcion ['opcion']
							);
						
						$menu[] = $menuAux;
					}
					
					$b = false;
						
					foreach ( $controladores as $c ) {
						if ($opcion ['controlador_src'] == $c) {
							$b = true;
							break;
						}
					}
					if (! $b) {
						$controladores [] = $opcion ['controlador_src'];
					}
					
					$r .= "{
								url : '" . $opcion ['url'] . "',
								template :  'vista/" . $opcion ['vista'] . "?$this->token',
										controller : '" . $opcion ['controlador'] . "'
							},";
				}
		
				$r = substr ( $r, 0, - 1 );
		
				$r = $this->getDefaultRutas () . $r;
		
				$rutas = "<script>var arrayRutas = [" . $r . "]; </script>";
				$scripts = $this->getDependenciasTop() . $this->getDefaultScripts () . "\n";
		
				foreach ( $controladores as $c ) {
					$scripts .= '<script src="controlador/' . $c . "?" . $this->token . '"></script> ' . "\n";
				}
		
				$scripts .= $this->getDependenciasBottom();
		
				$this->setRutas ( $rutas );
				$this->setScripts ( $scripts );
				$this->setMenus ( "<script>var menu = " . json_encode ( $menu ) . "</script>" );
			}
		} catch ( PDOException $e ) {
		}
	}
	
	private function getDependenciasTop(){
		return '<script src="libs/angular-1.4.8/angular.min.js?' . $this->token . '"></script>
			  	<script src="libs/angular-1.4.8/angular-route.js?' . $this->token . '"></script>
			  	<script src="libs/angular-1.4.8/angular-animate.min.js?' . $this->token . '"></script>
			  	<script src="libs/ng-table/dist/ng-table.min.js?' . $this->token . '"></script>
			  	<script src="libs/jquery/jquery.slim.min.js?' . $this->token . '"></script>
			  	<script src="libs/tether/tether.min.js?' . $this->token . '"></script>
				<script src="libs/bootstrap/js/bootstrap.min.js?' . $this->token . '"></script>';
	}
	
	private function getDefaultScripts(){
		return '<script src="controlador/ServicioRutas.js?' . $this->token . '"></script>
			<script src="controlador/ServicioAjax.js?' . $this->token . '"></script>
			<script src="controlador/ServicioUsuario.js?' . $this->token . '"></script>
			<script src="controlador/AppController.js?' . $this->token . '"></script>
			<script src="controlador/MenuPrincipalController.js?' . $this->token . '"></script>
			<script src="controlador/HomeController.js?' . $this->token . '"></script>
			<script src="controlador/ContrasenaController.js?' . $this->token . '"></script>
			<script src="controlador/DocumentosController.js?' . $this->token . '"></script>';
	}
	
	private function getDependenciasBottom(){
		return '<script src="libs/local/Funciones.js?' . $this->token . '"></script>
				<link rel="stylesheet"  href="libs/bootstrap-3.3.6/css/bootstrap.min.css?' . $this->token . '"/>
				<link rel="stylesheet" href="libs/local/custom_bootstrap.css?' . $this->token . '" />
				<link rel="stylesheet" href="libs/ng-table/dist/ng-table.min.css?' . $this->token . '" />
				<link rel="stylesheet" href="libs/jquery-ui/jquery-ui.min.css" />
				<link rel="stylesheet" href="libs/local/animaciones.css?' . $this->token . '" />
				<link rel="stylesheet" href="libs/local/estilo.css?' . $this->token . '" />
				<link rel="stylesheet" href="libs/local/simple-sidebar.css?' . $this->token . '" />';
		
	}
	
	private function getDependenciasLogin(){

		$dependencias = $this->getDependenciasTop();
		$dependencias .= '<script src="controlador/ServicioRutas.js?' . $this->token . '"></script>
			            <script src="controlador/ServicioAjax.js?' . $this->token . '"></script>
			            <script src="controlador/LoginController.js?' . $this->token . '"></script>
			            <link rel="stylesheet"  href="libs/b4/css/bootstrap.min.css?' . $this->token . '"/>
			            <link rel="stylesheet" href="libs/local/animaciones.css?' . $this->token . '" />';
	
		return $dependencias;
    }
	
	private function getDefaultRutas(){
		return "{
					url : '/',
					template :  'vista/Home.html?$this->token',
					controller : 'homeController'
				},
				{
					url : '/cambio_contrasena',
					template : 'vista/CambioContrasena.html" . "?$this->token" . "',
					controller : 'contrasenaController'
				},
				{
					url : '/manuales',
					template : 'vista/Documentos.html" . "?$this->token" . "',
					controller : 'documentosController'
				},";
	}
    
    public function getScripts() {
		return $this->scripts;
	}
    
    public function setScripts($scripts) {
		$this->scripts = $scripts;
    }
    
	public function getRutas() {
		return $this->rutas;
	}
    
    public function setRutas($rutas) {
		$this->rutas = $rutas;
	}
    
    public function getMenus() {
		return $this->menus;
	}
    
    public function setMenus($menus) {
		$this->menus = $menus;
    }
    
}