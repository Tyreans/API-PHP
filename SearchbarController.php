<?php

require_once("conexion.php");

class SearchbarController {

    private $conn;

    public function __construct() {
        // Conectamos con el rol público (tú puedes cambiarlo si quieres)
        $this->conn = conectarDB("invitado");
    }

    public function handleRequest() {
        header('Content-Type: application/json');

        try {
            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'search':
                    $this->search();
                    break;

                default:
                    $this->sendError("Acción no válida.");
            }

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    /**
     * Buscar en la tabla TAGS
     */
    private function search() {
        try {
            $query = trim($_GET['query'] ?? '');

            // Evita búsquedas muy cortas
            if (strlen($query) < 2) {
                $this->sendSuccess(['tags' => []]);
                return;
            }

            $tags_palabras = array_filter(array_map('trim', explode(' ', $query)));

            if (empty($tags_palabras)) {
                $tags_palabras = [$query];
            }

            $resultados = [];

            foreach ($tags_palabras as $tag) {

                // Convertimos espacios a _ como pedías
                $tag_busqueda = str_replace(' ', '_', $tag);
                $patron = '%' . strtoupper($tag_busqueda) . '%';

                // Consulta a MariaDB
                $sql = "
                    SELECT 
                        tag_id,
                        tag_name
                    FROM tags
                    WHERE UPPER(REPLACE(tag_name,' ','_')) LIKE :patron
                    LIMIT 10
                ";

                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':patron', $patron, PDO::PARAM_STR);
                $stmt->execute();

                $rows = $stmt->fetchAll();

                foreach ($rows as $row) {

                    // Evitar duplicados por ID
                    if (!isset($resultados[$row['tag_id']])) {
                        $resultados[$row['tag_id']] = [
                            'id'   => $row['tag_id'],
                            'tag'  => str_replace(' ', '_', $row['tag_name']),
                            'name' => $row['tag_name']
                        ];
                    }
                }
            }

            // Re-indexamos para devolver un array limpio
            $final = array_values($resultados);

            // Solo top 10
            $final = array_slice($final, 0, 10);

            $this->sendSuccess(['tags' => $final]);

        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function sendSuccess($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }

    private function sendError($msg) {
        echo json_encode([
            'success' => false,
            'error' => $msg
        ]);
        exit;
    }
}


$controller = new SearchbarController();
$controller->handleRequest();
?>
