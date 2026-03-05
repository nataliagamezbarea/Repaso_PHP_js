<?php

class GestorBuscador {

    public $conexion;
    public $titulo;
    public $campos = [];
    public $tablaPrincipal = '';
    public $joins = [];

    public $busquedaExcluido = [];
    public $edicionExcluido = [];
    public $edicionSoloLectura = [];
    public $creacionExcluido = [];

    public function __construct($host, $usuario, $pass, $base, $titulo, $campos=[], $tablaPrincipal='', $joins=[]) {
        $this->conexion = new mysqli($host, $usuario, $pass, $base);
        if($this->conexion->connect_error) die("Error de conexión: ".$this->conexion->connect_error);

        $this->titulo = $titulo;
        $this->campos = is_array($campos) ? $campos : [];
        $this->tablaPrincipal = $tablaPrincipal;
        // Si $joins no es array o está vacío, lo dejamos como array vacío
        if (!is_array($joins)) {
            $this->joins = [];
        } else {
            $this->joins = $joins;
        }
        // Permitir setear campos de solo lectura, búsqueda y creación excluida desde fuera
        if (isset($GLOBALS['edicionSoloLectura']) && is_array($GLOBALS['edicionSoloLectura'])) {
            $this->edicionSoloLectura = $GLOBALS['edicionSoloLectura'];
        }
        if (isset($GLOBALS['busquedaExcluido']) && is_array($GLOBALS['busquedaExcluido'])) {
            $this->busquedaExcluido = $GLOBALS['busquedaExcluido'];
        }
        if (isset($GLOBALS['creacionExcluido']) && is_array($GLOBALS['creacionExcluido'])) {
            $this->creacionExcluido = $GLOBALS['creacionExcluido'];
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = false;
            if(isset($_POST['editar_id'])) {
                $this->guardarEdicion($_POST['editar_id']);
                $accion = true;
            }
            if(isset($_POST['crear'])) {
                $this->crearFila();
                $accion = true;
            }

            if ($accion) {
                header("Location: ".$_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    public function tabla() {
        echo "<h1>{$this->titulo}</h1>";
        $this->buscador();
        echo '<button id="btnMostrarCrear" onclick="mostrarFormCrear()">Crear</button>';
        echo '<div id="contenedorFormCrear" style="display:none">';
        $this->formCrear();
        echo '</div>';
        $this->mostrarDatos();
        $this->js();
    }

    public function buscador() {
        echo "<form method='POST'><ul>";
        foreach($this->campos as $label => $campo) {
            if(in_array($campo,$this->busquedaExcluido)) continue;
            $valor = $_POST['b_'.str_replace('.','_',$campo)] ?? '';
            echo "<li><label>$label:</label> <input type='text' name='b_".str_replace('.','_',$campo)."' value='$valor'></li>";
        }
        echo "</ul><button type='submit'>Buscar</button></form><hr>";
    }

    public function formCrear() {
        echo "<form id='formCrear' method='POST' onsubmit='return ocultarFormCrearDespues()'><table border='1' cellpadding='5'><tr>";
        foreach((array)$this->campos as $label => $campo) {
            if (in_array($campo, $this->creacionExcluido)) continue;
            echo "<th>$label</th>";
        }
        echo "<th>Acciones</th></tr><tr>";
        foreach((array)$this->campos as $label => $campo) {
            if (in_array($campo, $this->creacionExcluido)) continue;
            $readonly = "";
            $extra = "";
            // Solo readonly si se está editando (el botón es Guardar)
            if (in_array($campo, $this->edicionSoloLectura)) {
                $extra = " data-only-edit='1'";
            }
            echo "<td><input type='text' name='p_".str_replace('.','_',$campo)."_form' id='input_".str_replace('.','_',$campo)."_form' data-campo='".str_replace('.','_',$campo)."' $readonly$extra></td>";
        }
        echo "<td><button type='submit' id='btnFormAccion' name='crear'>Crear</button></td></tr></table></form><hr>";
    }

    public function mostrarDatos() {
        $where = [];
        if (!is_array($this->campos)) $this->campos = [];
        // Proteger todos los foreach de campos
        foreach((array)$this->campos as $label => $campo) {
            $inputName = 'b_'.str_replace('.','_',$campo);
            if(!empty($_POST[$inputName])) {
                $valor = $this->conexion->real_escape_string($_POST[$inputName]);
                $where[] = "$campo LIKE '%$valor%'";
            }
        }

        $select = ["{$this->tablaPrincipal}.id AS row_id"];
        foreach((array)$this->campos as $label => $campo) $select[] = "$campo AS `$label`";

        $sql = "SELECT ".implode(", ", $select)." FROM {$this->tablaPrincipal}";
        foreach($this->joins as $j) $sql .= " {$j['tipo']} JOIN {$j['tabla']} ON {$j['on']}";
        if(!empty($where)) $sql .= " WHERE ".implode(" AND ", $where);

        $res = $this->conexion->query($sql);

        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach((array)$this->campos as $label => $campo) echo "<th>$label</th>";
        echo "<th>Acciones</th></tr>";

        if($res && $res->num_rows > 0) {
            while($fila = $res->fetch_assoc()) {
                // Buscar el valor de ID correctamente
                $id = isset($fila['ID']) ? $fila['ID'] : (isset($fila['row_id']) ? $fila['row_id'] : '');
                echo "<tr id='fila_$id'>";
                foreach((array)$this->campos as $label => $campo) {
                    // Mostrar el valor correcto para cada campo
                    if ($campo === 'usuarios.id' && isset($fila['ID'])) {
                        $valor = htmlspecialchars($fila['ID']);
                    } else {
                        $valor = htmlspecialchars($fila[$label] ?? '');
                    }
                    echo "<td>
                            <span class='texto'>$valor</span>
                          </td>";
                }
                echo "<td>
                        <button type='button' onclick='editarFila($id)'>✏️</button>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='".(count($this->campos)+1)."'>No se encontraron resultados</td></tr>";
        }

        echo "</table></form>";
    }

    public function guardarEdicion($id) {
        $updatesPrincipal = [];
        $updatesSecundarias = [];

        foreach($this->campos as $label => $campo) {
            if(in_array($campo,$this->edicionExcluido) || in_array($campo,$this->edicionSoloLectura)) continue;
            $key = 'p_'.str_replace('.','_',$campo).'_form';
            if(isset($_POST[$key])) {
                $val = $this->conexion->real_escape_string($_POST[$key]);
                $partes = explode('.',$campo);
                if($partes[0] === $this->tablaPrincipal) {
                    $updatesPrincipal[$partes[1]] = $val;
                } else {
                    $tabla = $partes[0];
                    if(!isset($updatesSecundarias[$tabla])) $updatesSecundarias[$tabla] = [];
                    $updatesSecundarias[$tabla][$partes[1]] = $val;
                }
            }
        }

        if(!empty($updatesPrincipal)) {
            $sets = [];
            foreach($updatesPrincipal as $c=>$v) $sets[] = "$c='$v'";
            $this->conexion->query("UPDATE {$this->tablaPrincipal} SET ".implode(',',$sets)." WHERE id='$id'");
        }

        foreach($updatesSecundarias as $tabla=>$campos) {
            $campoRel = $this->getCampoRelacion($tabla);
            $resCheck = $this->conexion->query("SELECT $campoRel[1] FROM $tabla WHERE $campoRel[1]='$id'");
            if($resCheck && $resCheck->num_rows>0) {
                $sets = [];
                foreach($campos as $c=>$v) $sets[] = "$c='$v'";
                $this->conexion->query("UPDATE $tabla SET ".implode(',',$sets)." WHERE $campoRel[1]='$id'");
            } else {
                $cols = [$campoRel[1]];
                $vals = [$id];
                foreach($campos as $c=>$v) { $cols[]=$c; $vals[]="'$v'"; }
                $this->conexion->query("INSERT INTO $tabla (".implode(',',$cols).") VALUES (".implode(',',$vals).")");
            }
        }
    }

    public function crearFila() {
        $camposPrincipal = [];
        $valoresPrincipal = [];
        $camposSec = [];

        foreach($this->campos as $label=>$campo) {
            $key = 'p_'.str_replace('.','_',$campo).'_form';
            $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            $partes = explode('.',$campo);
            // Si el campo es ID y está vacío, no lo incluimos en el insert
            if ($campo === $this->tablaPrincipal.'.id' && ($val === '' || $val === null)) {
                continue;
            }
            if($val === null || $val === '') $val = '';
            else $val = $this->conexion->real_escape_string($val);
            if($partes[0]===$this->tablaPrincipal) {
                $camposPrincipal[]=$partes[1];
                $valoresPrincipal[]="'".$val."'";
            } else {
                $tabla=$partes[0];
                if(!isset($camposSec[$tabla])) $camposSec[$tabla]=[];
                $camposSec[$tabla][$partes[1]]=$val;
            }
        }

        if(!empty($camposPrincipal)) {
            $this->conexion->query("INSERT INTO {$this->tablaPrincipal} (".implode(',',$camposPrincipal).") VALUES (".implode(',',$valoresPrincipal).")");
            $idNuevo = $this->conexion->insert_id;
            foreach($camposSec as $tabla=>$campos) {
                $campoRel = $this->getCampoRelacion($tabla);
                $cols=[$campoRel[1]]; $vals=[$idNuevo];
                foreach($campos as $c=>$v) { $cols[]=$c; $vals[]="'$v'"; }
                $this->conexion->query("INSERT INTO $tabla (".implode(',',$cols).") VALUES (".implode(',',$vals).")");
            }
        }
    }

    private function getCampoRelacion($tablaSec) {
        foreach($this->joins as $j) {
            if($j['tabla']===$tablaSec) {
                $partes = preg_split('/\s*=\s*/', $j['on']);
                if(count($partes)==2) return [trim($partes[0]), trim($partes[1])];
            }
        }
        return [$this->tablaPrincipal.'.id', $tablaSec.'.'.$this->tablaPrincipal.'_id'];
    }

    public function js() {
        echo "<script>
        function mostrarFormCrear() {
            limpiarForm();
            document.getElementById('contenedorFormCrear').style.display = 'block';
            document.getElementById('btnMostrarCrear').style.display = 'none';
            document.getElementById('btnFormAccion').innerText = 'Crear';
            document.getElementById('btnFormAccion').name = 'crear';
            // Mostrar inputs solo-edición como editables
            document.querySelectorAll('#formCrear input[data-only-edit]').forEach(function(input){
                input.readOnly = false;
                input.style.background = '';
                input.style.display = '';
            });
        }
        function mostrarFormEditar(id, valores) {
            document.getElementById('contenedorFormCrear').style.display = 'block';
            document.getElementById('btnMostrarCrear').style.display = 'none';
            let inputs = document.querySelectorAll('#formCrear input[data-campo]');
            inputs.forEach(function(input) {
                let campo = input.getAttribute('data-campo');
                if(valores[campo] !== undefined) {
                    input.value = valores[campo];
                } else {
                    input.value = '';
                }
            });
            document.getElementById('btnFormAccion').innerText = 'Guardar';
            document.getElementById('btnFormAccion').name = 'editar_id';
            document.getElementById('btnFormAccion').value = id;
            // Inputs solo-edición: readonly y fondo gris
            document.querySelectorAll('#formCrear input[data-only-edit]').forEach(function(input){
                input.readOnly = true;
                input.style.background = '#eee';
                input.style.display = '';
            });
        }
        function limpiarForm() {
            let inputs = document.querySelectorAll('#formCrear input[type=text]');
            inputs.forEach(i=>i.value='');
            document.getElementById('btnFormAccion').value = '';
        }
        function ocultarFormCrearDespues() {
            setTimeout(function(){
                document.getElementById('contenedorFormCrear').style.display = 'none';
                document.getElementById('btnMostrarCrear').style.display = 'inline';
                limpiarForm();
            }, 100); 
            return true;
        }

        function editarFila(id){
            let fila=document.getElementById('fila_'+id);
            let valores = {};
            let tds = fila.querySelectorAll('td');
            let campos = [];


            let ths = fila.parentElement.querySelectorAll('tr:first-child th');
            for(let i=0;i<ths.length-1;i++){
                let th = ths[i];


                let campo = document.querySelectorAll('#formCrear input[data-campo]')[i].getAttribute('data-campo');
                campos.push(campo);
            }
            for(let i=0;i<campos.length;i++){
                valores[campos[i]] = tds[i].innerText.trim();
            }
            mostrarFormEditar(id, valores);
        }
        </script>";
    }
}
?>