<?php

// Incluir el archivo de configuración
require_once '/apps/inc/config.php';

class FileUploadAPI {
    private $uploadDirectory;

    public function __construct($uploadDirectory) {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function handleFileUpload() {
        // Verificar si se ha recibido el archivo y no hay errores
        if (isset($_FILES['file_binary']) && $_FILES['file_binary']['error'] === UPLOAD_ERR_OK) {
            // Construir la ruta del fragmento del archivo
            $targetFileChunk = $this->uploadDirectory . uniqid('', true) . '_' . $_FILES['file_binary']['name'];

            $tempFile = $_FILES['file_binary']['tmp_name'];
            $originalName = $_FILES['file_binary']['name'];

            // Mover el fragmento a su ubicación temporal
            if (move_uploaded_file($tempFile, $targetFileChunk)) {
                // Verificar si este es el último fragmento
                if (isset($_POST['lastChunk']) && $_POST['lastChunk'] === 'true') {
                    // Construir la ruta del archivo final
                    $finalFile = $this->uploadDirectory . $originalName;

                    $finalFile = preg_replace('/\.part\d+$/', '', $finalFile);
    
                    // Abrir el archivo final para escritura
                    $fp = fopen($finalFile, 'wb');
                    
                    $pattern = $this->uploadDirectory . '*' . '.part*';
    
                    // Obtiene una lista de los fragmentos del archivo en la carpeta de carga
                    $chunks = glob($pattern);
    
                    // Ordena los fragmentos para asegurarse de que se reconstruyan en el orden correcto
                    sort($chunks);
    
                    foreach ($chunks as $chunk) {
                        // Lee el contenido del fragmento y escríbelo en el archivo final
                        fwrite($fp, file_get_contents($chunk));
                    }
    
                    // Cerrar el archivo final
                    fclose($fp);
    
                    // Eliminar los fragmentos temporales
                    foreach ($chunks as $chunk) {
                        unlink($chunk);
                    }

                    $response = ['success' => true, 'message' => 'Archivo subido exitosamente', 'filename' => $originalName];
                } else {
                    $response = ['success' => true, 'message' => 'Fragmento cargado exitosamente'];
                }
            } else {
                $response = ['success' => false, 'message' => 'No se pudo mover el fragmento del archivo cargado'];
            }
        } else {
            // Manejar posibles errores al subir el archivo
            switch ($_FILES['file_binary']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'El tamaño del archivo excede el límite';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = 'El archivo subido solo se cargó parcialmente';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = 'Ningún archivo fue subido';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = 'Falta carpeta temporal';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'No se pudo escribir el archivo en el disco';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $message = 'Una extensión PHP detuvo la carga del archivo';
                    break;
                default:
                    $message = 'Se produjo un error desconocido';
                    break;
            }
            $response = ['success' => false, 'message' => $message];
        }

        // Devolver la respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}

// Crear una instancia de la API y manejar la solicitud
$uploadAPI = new FileUploadAPI($uploadDirectory); 
$uploadAPI->handleFileUpload();
?>
