<?php

/*
|--------------------------------------------------------------------------
| Traduzione in spagnolo di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [
    'AUTHENTICATION_ERROR' => 'Acceso no autorizado',
    'SCAN_ERROR' => 'Error de escaneo',
    'VIRUS_FOUND' => 'Virus encontrado',
    'INVALID_FILE_EXTENSION' => 'Extensión de archivo no válida',
    'MAX_FILE_SIZE' => 'El archivo no puede exceder :max byte.',
    'INVALID_FILE_PDF' => 'Archivo PDF no válido',
    'MIME_TYPE_NOT_ALLOWED' => 'Tipo de archivo no permitido.',
    'INVALID_IMAGE_STRUCTURE' => 'Estructura de imagen no válida',
    'INVALID_FILE_NAME' => 'Nombre de archivo no válido',
    'ERROR_GETTING_PRESIGNED_URL' => 'Error al obtener la URL prefirmada',
    'ERROR_DURING_FILE_UPLOAD' => 'Error durante la carga del archivo',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Imposible guardar el archivo.',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Imposible crear la carpeta',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Imposible cambiar los permisos de la carpeta',
    'IMPOSSIBLE_SAVE_FILE' => 'Imposible guardar el archivo',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Error durante la creación del registro EGI en la base de datos',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Error durante la encriptación del nombre del archivo',
    'IMAGICK_NOT_AVAILABLE' => 'Problema interno, el soporte ya ha sido alertado',
    'JSON_ERROR_IN_DISPATCHER' => 'Error JSON en el despachador',
    'GENERIC_SERVER_ERROR' => 'Error genérico del servidor, el equipo técnico ha sido informado',
    'FILE_NOT_FOUND' => 'Archivo no encontrado',
    'UNEXPECTED_ERROR' => 'Error genérico del servidor, el equipo técnico ha sido informado',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Error al eliminar el archivo temporal local',

    'scan_error' => 'Error de escaneo',
    'virus_found' => 'Virus encontrado',
    'required' => 'El campo es obligatorio.',
    'file' => 'Se produjo un error al cargar el archivo.',
    'file_extension_not_valid' => 'Extensión de archivo no válida',
    'mimes' => 'El archivo debe ser de tipo: :values.',
    'max_file_size' => 'El archivo no puede exceder :max byte.',
    'invalid_pdf_file' => 'Archivo PDF no válido',
    'mime_type_not_allowed' => 'Tipo de archivo no permitido.',
    'invalid_image_structure' => 'Estructura de imagen no válida',
    'invalid_file_name' => 'Nombre de archivo no válido',
    'error_getting_presigned_URL' => 'Error al obtener la URL prefirmada',
    'error_getting_presigned_URL_for_user' => 'Error al cargar el archivo',
    'error_during_file_upload' => 'Error durante la carga del archivo',
    'error_deleting_file' => 'Error al eliminar el archivo',
    'upload_finished' => 'Carga final',
    'some_errors' => 'algunos errores',
    'upload_failed' => 'carga fallida',
    'error_creating_folder' => 'Error al crear la carpeta',
    'error_changing_folder_permissions' => 'Error al cambiar los permisos de la carpeta',
    'local_save_failed_file_saved_to_external_disk_only' => 'El guardado local falló, archivo guardado solo en el disco externo',
    'external_save_failed_file_saved_to_local_disk_only' => 'El guardado externo falló, archivo guardado solo en el disco local',
    'file_scanning_may_take_a_long_time_for_each_file' => 'El escaneo de archivos puede llevar mucho tiempo para cada archivo',
    'all_files_are_saved' => 'Todos los archivos están guardados',
    'loading_finished_you_can_proceed_with_saving' => 'Carga finalizada, puede proceder con el guardado',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Carga finalizada, puede proceder con el guardado y el escaneo',
    'im_uploading_the_file' => 'Estoy subiendo el archivo',


    'exception' => [
        'NotAllowedTermException' => 'Término no permitido',
        'MissingCategory' => 'Es necesario insertar una categoría.',
        'DatabaseException' => 'Se produjo un error de base de datos',
        'ValidationException' => 'Se produjo un error de validación',
        'HttpException' => 'Se produjo un error HTTP',
        'ModelNotFoundException' => 'Modelo no encontrado',
        'QueryException' => 'Error de consulta',
        'MintingException' => 'Error durante el minting',
        'FileNotFoundException' => 'Archivo no encontrado',
        'InvalidArgumentException' => 'Argumento no válido',
        'UnexpectedValueException' => 'Valor inesperado',
        'ItemNotFoundException' => 'Elemento no encontrado',
        'MultipleItemsFoundException' => 'Múltiples elementos encontrados',
        'LogicException' => 'Excepción lógica',
        'EntryNotFoundException' => 'Entrada no encontrada',
        'RuntimeException' => 'Error de tiempo de ejecución',
        'BadMethodCallException' => 'Llamada de método incorrecta',
        'LockTimeoutException' => 'Tiempo de espera de bloqueo',
        'InvalidIntervalException' => 'Intervalo no válido',
        'InvalidPeriodParameterException' => 'Parámetro de período no válido',
        'EndLessPeriodException' => 'Período sin fin',
        'UnreachableException' => 'Excepción inalcanzable',
        'InvalidTimeZoneException' => 'Zona horaria no válida',
        'ImmutableException' => 'Excepción inmutable',
        'InvalidFormatException' => 'Formato no válido',
    ],
    'forbidden_term_warning' => "
        <div style=\"text-align: left;\">
            <p>Estimado usuario,</p>
            </br>
            <p>El texto que ha introducido viola nuestras normas y directrices de la comunidad. Por favor, modifique el contenido e inténtelo de nuevo.</p>
            </br>
            <p>Si no entiende por qué este término está prohibido, consulte las cláusulas del acuerdo que aceptó en el momento del registro.
            <p>Agradecemos su comprensión y colaboración.</p>
            </br>
            <p>Atentamente,
            <br>
            El equipo de Frangette</p>
        </div>",

    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            Consulte la página de normas de la comunidad.
        </a>.',

    'forbiddenTermChecker_was_not_initialized_correctly' => 'ForbiddenTermChecker no se inicializó correctamente',
    'table_not_exist' => 'La tabla no existe',
    'unique' => 'Este valor ya está presente en su biblioteca de traits.',
    'the_category_name_cannot_be_empty' => 'El nombre de la categoría no puede estar vacío',
    'nathing_to_save' => 'Nada que guardar',
    'an_error_occurred' => '¡Ups! Lo siento, ¡se produjo un error!',
    'error_number' => 'Número de error:',
    'reason' => [
        'reason' => 'razón',
        'wallet_not_valid' => 'Cartera no válida',
        'something_went_wrong' => 'Algo salió mal',
    ],
    'solution' => [
        'solution' => 'solución',
        'create_a_new_wallet_and_try_again' => 'Crea una nueva cartera y vuelve a intentarlo',
        'we_are_already_working_on_solving_the_problem' => 'Ya estamos trabajando en resolver el problema',
    ],
    'min' => [
        'string' => 'El campo debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo debe tener como máximo :max caracteres.',
    ],
    'id_epp_not_found' => 'ID EPP no encontrado',
    'minting' => [
        'error_generating_token' => 'Error al generar el token',
        'insufficient_wallet_balance' => 'Saldo insuficiente en la cartera para comprar este EcoNFT',
        'error_during_save_the_metadataFile' => 'Error al guardar los metadatos en el archivo',
        'error_during_save_the_metadata_on_database' => 'Error al guardar los metadatos en la base de datos',
        'error_during_create_metadata_file' => 'Error al crear el archivo de metadatos',
        'error_during_save_the_buyer' => 'Error al guardar el comprador',
        'buyer_not_exist' => 'El comprador no existe',
        'this_wallet_does_not_belong_to_any_buyer' => 'Esta cartera no pertenece a ningún comprador',
        'seller_not_exist' => 'El vendedor no existe',
        'seller_owner_not_found' => 'No se encuentra el propietario del vendedor',
        'seller_wallet_address_not_found' => 'No se encontró la dirección de la cartera del vendedor',
        'error_during_save_the_seller' => 'Error al guardar el vendedor',
        'error_during_save_the_buyer_transaction' => 'Error al guardar la transacción del comprador',
        'error_during_the_saving_of_the_payment' => 'Error al guardar el pago',
        'error_during_save_the_natan' => 'Error al guardar los datos', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Error al guardar la transacción',
        'seller_not_found' => 'Vendedor no encontrado',
        'error_during_the_minting' => 'Error durante la creación',
        'error_uploading_file' => 'Error al subir el archivo',
        'insufficient_balance' => 'Saldo insuficiente',
        'eco_nft_not_found' => 'EcoNFT no encontrado',
        'no_traits_found' => 'No se encontraron rasgos',
    ],
];
