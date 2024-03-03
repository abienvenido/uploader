localStorage.removeItem('chunkIndex');

// Obtener referencia al área de arrastrar y soltar
const dragArea = document.getElementById('dragArea');

// Obtener referencia al input de tipo archivo
const fileInput = document.getElementById('fileInput');

// Obtener referencia al div donde se mostrará la información del archivo
const fileInfoDiv = document.getElementById('fileInfoDiv');

//------------------------------------------------------------------------------------------------------------------------
// Evento button uploader 
//------------------------------------------------------------------------------------------------------------------------

// Escuchar el evento de cambio en el input de tipo archivo
fileInput.addEventListener('change', function() {
    // Verificar si se seleccionó un archivo
    if (this.files.length > 0) {
        // Obtener el nombre del archivo
        const fileName = this.files[0].name;
        // Obtener el tamaño del archivo en MB
        const fileSize = (this.files[0].size / (1024 * 1024)).toFixed(2); // Convertir a MB con dos decimales
        // Crear el contenido HTML para mostrar la información del archivo
        const fileInfoHTML = `
            <div class='nameFile'>${fileName}</div>
            <div>${fileSize} MB</div>
            <progress id="progressBar" max="100" value="0"></progress>
            <span id="progressValue">0%</span>
        `;
        // Asignar el contenido HTML al div de información del archivo
        fileInfoDiv.innerHTML = fileInfoHTML;
        // Mostrar el div de información del archivo
        fileInfoDiv.style.display = 'block';

        const uploadButton = document.getElementById('uploadButton');

        uploadButton.style.display = 'block'; // Mostrar el botón uploadButton

        // Eliminamos la clase para que esté de nuevo habilitadop
        document.getElementById('uploadButton').classList.remove('disabled');
        
    } else {
        // Si no se seleccionó ningún archivo, limpiar el contenido del div
        fileInfoDiv.innerHTML = '';
        // Ocultar el div de información del archivo
        fileInfoDiv.style.display = 'none';
        uploadButton.style.display = 'none'; // Ocultar el botón uploadButton
    }
});

//------------------------------------------------------------------------------------------------------------------------
// Evento Drag And Drop 
//------------------------------------------------------------------------------------------------------------------------

// Escuchar el evento de arrastrar sobre el área
dragArea.addEventListener('dragover', function(event) {
    event.preventDefault(); // Evitar el comportamiento predeterminado del navegador
    dragArea.classList.add('dragging'); // Añadir clase "dragging" para resaltar el área de arrastrar y soltar
});

// Escuchar el evento de soltar en el área
dragArea.addEventListener('drop', function(event) {
    event.preventDefault(); // Evitar el comportamiento predeterminado del navegador
    dragArea.classList.remove('dragging'); // Quitar clase "dragging" al soltar el archivo

    // Obtener el archivo que se soltó
    const file = event.dataTransfer.files[0];

    if (file) { // Verificar si se soltó un archivo
        // Obtener el nombre del archivo
        const fileName = file.name;
        // Obtener el tamaño del archivo en MB
        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convertir a MB con dos decimales

        // Crear el contenido HTML para mostrar la información del archivo
        const fileInfoHTML = `
            <div class='nameFile'>${fileName}</div>
            <div>${fileSize} MB</div>
            <progress id="progressBar" max="100" value="0"></progress> 
            <span id="progressValue">0%</span>
        `;
        // Asignar el contenido HTML al div de información del archivo
        fileInfoDiv.innerHTML = fileInfoHTML;
        // Mostrar el div de información del archivo
        fileInfoDiv.style.display = 'block';

        // Llamar a la función de carga de archivos con la información relevante del archivo
        uploadFileDrag(file);
    } else {
        // Si no se seleccionó ningún archivo, limpiar el contenido del div
        fileInfoDiv.innerHTML = '';
        // Ocultar el div de información del archivo
        fileInfoDiv.style.display = 'none';
    }
});

// Escuchar el evento de dejar de arrastrar fuera del área
dragArea.addEventListener('dragleave', function(event) {
    event.preventDefault(); // Evitar el comportamiento predeterminado del navegador
    dragArea.classList.remove('dragging'); // Quitar clase "dragging" al dejar de arrastrar fuera del área
});

//------------------------------------------------------------------------------------------------------------------------
// Manejo de subida del fichero
//------------------------------------------------------------------------------------------------------------------------

// Función Genérica
async function handleFileUpload(file) {
    const url = 'http://localhost:8899/upload.php';
    const chunkSize = 1024 * 1024; // 1MB

    let chunkIndex = localStorage.getItem('chunkIndex') || 0;
    chunkIndex = parseInt(chunkIndex);
    let start = chunkIndex * chunkSize;
    let end = Math.min(start + chunkSize, file.size);
    const totalChunks = Math.ceil(file.size / chunkSize);

    let uploadSuccess = false;

    while (start < file.size) {
        const chunk = file.slice(start, end);
        const formData = new FormData();
        formData.append('file_binary', chunk, file.name + '.part' + chunkIndex);

        // Si este es el último fragmento, marcarlo como tal
        if (chunkIndex === totalChunks - 1) {
            formData.append('lastChunk', 'true');
        }

        try {
            const response = await postFormData(url, formData);
            console.log(response); // Response from server

            // Mostrar el progreso y el mensaje de estado
            const progress = Math.round(((chunkIndex + 1) / totalChunks) * 100);
            const progressValue = document.getElementById('progressValue');
            const progressBarId = 'progressBar';
            document.getElementById(progressBarId).value = progress;
            progressValue.textContent = progress + '%';
            document.getElementById('status').innerText = response.message;

            if (response.success && chunkIndex === totalChunks - 1) {
                document.getElementById('status').innerText = response.message;
                uploadSuccess = true;
                localStorage.removeItem('chunkIndex');
                localStorage.removeItem('start');
                document.getElementById('uploadButton').disabled = true;
                break;
            }

            start = end;
            end = Math.min(start + chunkSize, file.size);
            chunkIndex++;

        } catch (error) {
            console.log(error);
            // Mostrar errores en la interfaz de usuario
            document.getElementById('status').innerText = 'Error: ' + error.message;
            break; // Detener la subida en caso de error
        }

        // Guardar el índice del fragmento en la memoria del navegador
        localStorage.setItem('chunkIndex', chunkIndex);
        // Guardar el tamaño en bytes
        localStorage.setItem('start', start);
    }

    if (!uploadSuccess) {
        document.getElementById('status').innerText = 'Cargando...';
        setTimeout(() => {
            // Llamar a la función de carga de archivos con el archivo nuevamente
            handleFileUpload(file);
        }, 500); // Intentar nuevamente después de 0,5 segundos
    }

    // Habilitar el botón de carga después de completar la subida
    document.getElementById('uploadButton').disabled = false;
}

// Llamada a la función handleFileUpload para el evento button uploader
async function uploadFile() {
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    if (!file) {
        document.getElementById('status').innerText = 'Error: No se ha seleccionado ningún archivo.';
        return;
    }
    await handleFileUpload(file);
}

// Llamada a la función handleFileUpload para el evento drag and drop
async function uploadFileDrag(file) {
    await handleFileUpload(file);
}

//------------------------------------------------------------------------------------------------------------------------
// POST: Llamada al backend de PHP
//------------------------------------------------------------------------------------------------------------------------
async function postFormData(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        body: formData
    });
    return await response.json();
}
