<?php

// TODO:
// Manejo de fragmentos: En lugar de guardar los fragmentos de archivo en un directorio temporal y
// luego concatenarlos en el archivo final, podrías escribir directamente los fragmentos 
// en el archivo final a medida que llegan. Esto evitaría la necesidad de almacenar múltiples 
// fragmentos de archivos en disco y reduciría el uso de recursos del servidor.

// Utilizar lo siguiente:
    // $fp = fopen($finalFile, 'ab');        
    // $chunk = fopen($targetFileChunk, 'rb'); // Abrir el fragmento cargado
    // stream_copy_to_stream($chunk, $fp); // Copiar el contenido del fragmento al archivo final

// Directorio donde se guardarán los archivos subidos
$uploadDirectory = 'uploads/';

// Verificar si se ha recibido el archivo y no hay errores
if (isset($_FILES['file_binary']) && $_FILES['file_binary']['error'] === UPLOAD_ERR_OK) {
    $tempFile = $_FILES['file_binary']['tmp_name'];
    $originalName = $_FILES['file_binary']['name'];
    $fileSize = $_FILES['file_binary']['size'];
    $fileType = $_FILES['file_binary']['type'];

    $uniqueId = uniqid('', true);
    $chunkIndex = isset($_POST['chunkIndex']) ? $_POST['chunkIndex'] : 0;
    $totalChunks = isset($_POST['totalChunks']) ? $_POST['totalChunks'] : 1;

    // Construir la ruta del fragmento del archivo
    $pattern = $uploadDirectory . '*' . '_' . $originalName;
    $fileExists = glob($pattern);
    $targetFileChunk = $uploadDirectory . $uniqueId . '_' . $originalName;

    // Mover el fragmento a su ubicación temporal
    if (empty($fileExists)) {
        if (move_uploaded_file($tempFile, $targetFileChunk)) {
            // Verificar si este es el último fragmento
            if (isset($_POST['lastChunk']) && $_POST['lastChunk'] === 'true') {
                // Construir la ruta del archivo final
                // $finalFile = $uploadDirectory . $uniqueId . '_' . $originalName;

                $finalFile = $uploadDirectory . $originalName;

                $finalFile = preg_replace('/\.part\d+$/', '', $uploadDirectory . $originalName);

                // Abrir el archivo final para escritura
                $fp = fopen($finalFile, 'wb');
                
                $pattern = $uploadDirectory . '*' . '.part*';

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

                $response = ['success' => true, 'message' => 'Archivo reconstruido exitosamente', 'filename' => $uniqueId . '_' . $originalName];
            } else {
                $response = ['success' => true, 'message' => 'Fragmento cargado exitosamente'];
            }
        } else {
            $response = ['success' => false, 'message' => 'No se pudo mover el fragmento del archivo cargado'];
        }
    }else {
        $response = ['success' => true, 'message' => 'Ya existe el fichero'];
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
            $message = 'Ningun archivo fue subido';
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
?>
